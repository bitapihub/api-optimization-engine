<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Process Data Call requests
 */

namespace V1\Call;

class DataCall
{
	/**
	 * Run the Data Call
	 * 
	 * @return mixed The array of responses and template data, or the HTML template if the format is "html"
	 */
	public static function run()
	{
		if (\Session::get('public', true) === true) {
			return \Utility::format_error(400, \V1\Err::NO_JS_CALLS, \Lang::get('v1::errors.no_js_calls'));
		}
		
		// Make sure the Data Call exists, and is part of their account.
		if (empty($data_call_data = \V1\Model\DataCalls::get_data_call())) {
			return \Utility::format_error(404,\V1\Err::BAD_DATA_CALL, \Lang::get('v1::errors.bad_data_call'));
		}
		
		$account_data = \V1\Model\Account::get_account();
		
		// Make sure they are allowed to access it.
		if ($account_data['access_level'] < $data_call_data['min_access_level']) {
			return \Utility::format_error(402, \V1\Err::UPGRADE_REQUIRED, \Lang::get('v1::errors.upgrade_required'));
		}
		
		// Make sure that the Data Call is enabled or callable.
		if (
			$data_call_data['active_level'] === 0 &&
			(
				$account_data['can_run_inactive'] === 0 &&
				$account_data['id'] !== $data_call_data['account_id']
			)
		) {
			return \Utility::format_error(403, \V1\Err::DISABLED_DATA_CALL, \Lang::get('v1::errors.disabled_data_call'));
		}
		
		// Custom Data Calls allow the user to send us their workload (call script) and we'll process it for them.
		if (\V1\APIRequest::get('data-call', false) === 'custom') {
			$call_script = \V1\APIRequest::get('call-script', false);
		} else {
			$call_script = json_decode($data_call_data['call_script'], true);
		}
		
		// Make sure we have a call script.
		if (empty($call_script) || !is_array($call_script)) {
			return \Utility::format_error(503, \V1\Err::DATA_CALL_MISCONFIG, \Lang::get('v1::errors.data_call_misconfig'));
		}
		
		// Free accounts may not change their Data Calls, as they may only use public data calls.
		if (
			$account_data['access_level'] > 1 &&
			is_array($call_options = \V1\APIRequest::data_call('call-options', false))
		) {
			$call_script = array_replace_recursive($call_script, $call_options);
		}
		
		/*
		 * Set the Data Call flag to bypass things that no longer pertain to further calls, such as
		 * authentication to Bit API Hub.
		 */
		\Session::set('data_call', true);
		
		$response = array();
		$template_data = null;
		foreach ($call_script as $key => $call) {
			
			// If we have template data for widgets, gadgets, graphs, and charts, oh my, then we'll use it later.
			if ($key === 'template') {
				
				$template_data = $call;
				continue;
				
			}
			
			// We need a name. Even custom calls use the "custom" API.
			if (empty($call['api'])) {
				continue;
			}
			
			// Set the post data as defined in our script.
			\Session::set('posted_data', $call);
			
			// Make the call.
			$api_call = \Request::forge('v1/index', false)->execute()->response->body;
			
			// Bad decode
			if (empty($api_call)) {
				$response[$call['api']][] = \Utility::format_error(500);
			}
			
			// The response cometh forth, not stuck in zein queue.
			if (
				!empty($api_call[0]['response']['body']) &&
				$api_call[0]['response']['body'] === \V1\Constant::QUEUED_CALL
			) {
				
				// Keep the order of calls right proper. :P
				$response[$call['api']][] = \V1\Constant::QUEUED_CALL;
				
			} else {
				
				// We have our response, so we set that now.
				$response[$call['api']][] = $api_call[0];
				
			}
			
		}
		
		// If the customer doesn't need any response data, then we don't make them wait while we retrieve the results.
		if (\V1\APIRequest::data_call('no-response', false) === true) {
			
			$response = array(
				
				'status'	=> 200,
				'headers'	=> array(),
				'body'		=> \Lang::get('v1::response.done'),
				
			);
			return \Utility::format_response(200, $response);
			
		}
		
		// Check for responses.
		$call_queue = \V1\Socket::forge()->get_results();
		
		// If we have queued responses, and possibly place holders, then we'll loop.
		if (!empty($call_queue) && !empty($response)) {
			
			// Let's loop. :)
			foreach ($call_queue as $api_name => $call_number_data) {
				
				/*
				 * Somehow we don't need the response. Odd... I don't know why they bother giving me this stuff
				 * if they don't want me to use it.
				 */
				if (empty($response[$api_name])) {
					continue;
				}
				
				// Find the next queued placeholder.
				$queued_placeholder = array_search(\V1\Constant::QUEUED_CALL, $response[$api_name]);
				
				// If we have a placeholder, then put it's value in place.
				if ($queued_placeholder !== false) {
					$response[$api_name][$queued_placeholder] = $call_number_data[(int)key($call_number_data)];
				}
				
			}
			
		}
		
		// If we have template data to display, then format that now.
		if (!empty($template_data)) {
			
			// We only want the template we just compiled, so return that.
			if (\Session::get('response_format') === 'html') {
				return static::process_template($template_data, $response, true);
			}
			
			// Set the template to the array of template data.
			$response['template'] = static::process_template($template_data, $response);
			return \Utility::format_response(200, $response);
			
		} else {
			
			// No template data, so just return the responses.
			return \Utility::format_response(200, $response);
			
		}
		
	}
	
	/**
	 * Parse the HTML data
	 * 
	 * @param array $template		The array of template data from the Data Call call script
	 * @param array $api_responses	The array of responses we ran with the Data Call
	 * @param bool $return_html		Set to true to return only HTML, not the array of parsed template data
	 * 
	 * @return mixed The array of parsed template data, or a string of HTML body contents if $return_html is true
	 */
	protected static function process_template(array $template, array $api_responses, $return_html = false)
	{
		// Fill out our variables if we have some. (Kind of pointless not to.)
		if (!empty($template['variables'])) {
			
			$variables = array();
			foreach ($template['variables'] as $variable => $value_location) {
				
				$variables[$variable] = null;
				
				// Check if it resolves.
				if (($value = \Arr::get($api_responses, $value_location, 'BAH_NO_VALUE')) !== 'BAH_NO_VALUE') {
				
					// Add the value to the variable so we can replace it.
					if (is_numeric($value) || is_string($value) || is_null($value)) {
						$variables[$variable] = (string) $value;
					}
					
					// Boolean doesn't like to get cast to a string properly.
					if (is_bool($value)) {
						$variables[$variable] = $value === false ? 'false' : 'true';
					}
				
				}
				
			}
			
			// We don't return these.
			unset($template['variables']);
			
			// Replace the variables in the template.
			if (!empty($variables)) {
				$template = str_replace(array_keys($variables), array_values($variables), $template);
			}
			
		}
		
		$account_data = \V1\Model\Account::get_account();
		
		/**
		 * Add our link back on free accounts, or account who wish to show a link back. While free accounts
		 * could just decide not to show the body, they cannot configure their own Data Calls to place body
		 * content in "css" or "js". Anyone can just preg_replace() away the linkback, but in general,
		 * free accounts will show our link back.
		 */
		if (
			!empty($template['body']) &&
			(
				$account_data['access_level'] === 1 ||
				$account_data['link_back'] === 1
			)
		) {
			
			$template['body'] .= \View::forge(
				'linkback',
				array(
					'color' => \V1\APIRequest::data_call('linkback-color', 'dark'),
				),
				false
			)->render();
			
		}
		
		// We'll parse the template for them.
		if ($return_html === true) {
			
			$html = null;
			
			// CSS should be in <style> tags.
			$html .= !empty($template['css']) ? $template['css'].'<body>' : null;
			
			// HTML body
			$html .= !empty($template['body']) ? $template['body'] : null;
			
			// JS comes at the end to keep things speedy.
			$html .= !empty($template['js']) ? $template['js'] : null;
		
			return $html;
			
		}
		
		// Send the template data sectioned out for use.
		return $template;
		
	}
}

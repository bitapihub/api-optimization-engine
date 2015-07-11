<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Dynamic call object
 */

namespace V1\Call;

class DynamicCall
{
	/**
	 * Configure a dynamic call
	 *
	 * @return mixed The \V1\APICall object or the error array on fail
	 */
	public static function configure_call()
	{
		// Dynamic calls don't work through JS.
		if (\Session::get('public', true) === true) {
			return \Utility::format_error(400, \V1\Err::NO_JS_CALLS, \Lang::get('v1::errors.no_js_calls'));
		}
		
		// We need API configuration data.
		if (!is_array(\V1\APIRequest::get('configure', false))) {
			return \Utility::format_error(400, \V1\Err::MISSING_CONFIGURE, \Lang::get('v1::errors.missing_configure'));
		}
		
		// For some reason we can't parse the RAML, so we throw a 500 error.
		if (($api_def = \V1\RAML::parse()) === false) {
			return \Utility::format_error(500);
		} elseif (is_array($api_def)) {
		
			// Specific error
			return $api_def;
		
		}
			
		if (
			is_string($uri = \V1\APIRequest::get('configure.uri')) &&
			is_string($method = \V1\APIRequest::get('configure.method'))
		) {
		
			$api_call = null;
			
			// Custom calls
			if (\V1\APIRequest::get('api') === 'custom') {
				
				if (is_string($url = \V1\APIRequest::get('configure.url')) && !empty($url)) {
					$api_call = \V1\APICall::forge($api_def, $method, $uri, null, true, $url);
				}
				
				return \Utility::format_error(400, \V1\Err::NO_URL, \Lang::get('v1::errors.no_url'));
				
			}
			
			$api_data = \V1\Model\APIs::get_api();
			
			try {
				
				// Is it a valid resource?
				$api_def->getResourceByUri($uri)->getMethod($method);
				
				// We'll validate the call unless both the API provider and calling script deny that protection.
				$custom_dynamic = false;
				if ($api_data['force_validation'] === 0 && \V1\APIRequest::get('no-validate', false) === true) {
					$custom_dynamic = true;
				}
				
				$api_call = \V1\APICall::forge($api_def, $method, $uri, null, $custom_dynamic);
				
			} catch (\Raml\Exception\BadParameter\ResourceNotFoundException $e) {

				// Does the API Provider allow for unconfigured static calls on their server?
				if ($api_data['allow_custom_dynamic'] === 1) {
					$api_call = \V1\APICall::forge($api_def, $method, $uri, null, true);
				}
				
			}
		
			if (is_object($api_call)) {
				
				if (!empty($api_call->get_errors())) {
				
					// Errors from \APICall
					return $api_call->get_errors();
				
				} else {
				
					// Return the \APICall object
					return $api_call;
						
				}
				
			}
			
		}
		
		// Not found
		return \Utility::format_error(400, \V1\Err::BAD_DYNAMIC, \Lang::get('v1::errors.bad_dynamic'));
	}
}

<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * OAuth 1.0a call object
 * 
 * @link http://oauth.net/core/1.0a/
 */

namespace V1\SecurityCall;

class Custom extends \V1\SecurityCall\Template\SecurityCallAbstract
{
	/**
	 * Signify if the object is in error status
	 * @var bool
	 */
	public $error = false;
	
	/**
	 * The current instance
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Forge a new object
	 * 
	 * @return object
	 */
	public static function forge()
	{
		if (empty(static::$instance)) {
			static::$instance = new static;
		}
		
		return static::$instance;
	}
	
	/**
	 * Run the request
	 * 
	 * @param \Raml\SecurityScheme $securityscheme_obj The security scheme to process the call data for
	 * @param \V1\APICall $apicall_obj					The APICall object
	 * 
	 * @return mixed The object we just completed or an array describing the next step in the security process
	 */
	public function run(\Raml\SecurityScheme $securityscheme_obj, \V1\APICall $apicall_obj)
	{
		$settings		= $securityscheme_obj->getSettings();
		$credentials	= $apicall_obj->get_credentials();
		
		// Save the credentials
		\V1\Keyring::set_credentials($credentials);
		
		/**
		 * By default we'll return the response from the authentication request so that it's meaningful.
		 * However, in doing so, we'll need to block the main request, so developers may set this flag
		 * to ignore the authentication, signifying that they've already got the information they needed
		 * from it.
		 * 
		 * NOTE: This security method is meant as a basic way to catch security methods we otherwise
		 * haven't implemented in our system. Take it for what it's worth. 
		 * 
		 * @TODO When using this security method, skip processing the APICall object for a speedup.
		 */
		if (!empty($credentials['CUSTOM_IGNORE_AUTH'])) {
			return true;
		}
		
		
		// Remove unused credentials so as not to replace bad variables in the template.
		foreach ($credentials as $variable => $entry) {
			
			if (strpos($variable, 'CUSTOM_') !== 0) {
				unset($credentials[$variable]);
			}
			
		}
		
		// We need the method or we'll fail the call.
		if (empty($settings['method'])) {
			
			$this->error = true;
			return $this;
			
		}
		
		// Normalize the data into arrays.
		$described_by	= $securityscheme_obj->getDescribedBy();
		$headers		= $this->get_param_array($described_by->getHeaders());
		$query_params	= $this->get_param_array($described_by->getQueryParameters());
		$bodies			= $described_by->getBodies();
		$method			= \Str::upper($settings['method']);
		$url			= $settings['url'];
		
		// Grab the body if we have one, and the method supports one.
		$body = null;
		$body_type = null;
		if (count($bodies) > 0 && !in_array($method, array('GET', 'HEAD'))) {
			
			reset($bodies);
			$body_type = key($bodies);
			$body = $bodies[$body_type]->getExamples()[0];
			
		}
		
		/**
		 * NOTE: These replacements may ruin the formatting or allow for people to inject data into them.
		 * API Providers should be aware of that possibility.
		 * 
		 * @TODO In the future, we can consider implementing checking to verify that people aren't sending
		 * crap data through the system.
		 */
		$headers		= $this->remove_cr_and_lf($this->replace_variables($headers, $credentials));
		$query_params	= $this->remove_cr_and_lf($this->replace_variables($query_params, $credentials));
		$body			= $this->replace_variables($body, $credentials);
		
		if (!empty($query_params)) {
			
			$query_string = http_build_query($query_params, null, '&');
			if (strpos($url, '?') === false) {
				$url .= '?'.$query_string;
			} else {
				$url .= '&'.$query_string;
			}
			
		}
		
		/**
		 * RUNCLE RICK'S RAD RUN CALLS (The second coming!)
		 */
		
		$curl = \Remote::forge($url, 'curl', $method);
		
		// Set the headers
		$headers = \V1\RunCall::get_headers($headers);
		
		foreach ($headers as $header_name => $header_value) {
			$curl->set_header($header_name, $header_value);
		}
		
		// Return the headers
		$curl->set_option(CURLOPT_HEADER, true);
		
		// If we need a body, set that.
		if (!empty($body) && !in_array($method, array('GET', 'HEAD'))) {
			
			$curl->set_header('Content-Type', $body_type);
			$curl->set_params($body);
			
		}
		
		// Run the request
		try {
			$response = $curl->execute()->response();
		} catch (\RequestStatusException $e) {
			$response = \Remote::get_response($curl);
		} catch (\RequestException $e) {
			
			$this->error = true;
			return $this;
			
		}
		
		// Set the usage stats, and format the response
		return \V1\Socket::prepare_response(array(
			
			'status'	=> $response->status,
			'headers'	=> $response->headers,
			'body'		=> $response->body,
			
		));
	}
	
	/**
	 * Normalize the PHP RAML Parser object arrays into array data
	 * 
	 * @param array $params The array of param data to normalize
	 * @return array The normalized array (May be empty)
	 */
	private function get_param_array(array $params)
	{
		$params_list = array();
		foreach ($params as $params_name => $np_obj) {
				
			if (($value = $np_obj->getDefault()) !== null) {
				$params_list[$params_name] = $value;
			}
				
		}
		
		return $params_list;
	}
	
	/**
	 * Replace the credential variables with their respective credential data
	 * 
	 * @param mixed $subject		The array or string to replace credentials in
	 * @param array $credentials	The array of credential data to set for the variables
	 * 
	 * @return mixed The altered version of $subject
	 */
	private function replace_variables($subject, array $credentials)
	{
		return str_replace(array_keys($credentials), array_values($credentials), $subject);
	}
	
	/**
	 * Protect against data corruption and attacks
	 * 
	 * @param mixed $subject_delta The data to remove carriage returns and line feeds from
	 * @return mixed The altered version of $subject_delta, no longer bonded to his little sister
	 */
	private function remove_cr_and_lf($subject_delta)
	{
		return str_replace(array("\r", "\n", '\r', '\n'), '', $subject_delta);
	}
}

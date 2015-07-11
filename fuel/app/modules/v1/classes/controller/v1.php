<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Controller file for version 1 of the Bit API Hub API Engine
 */

namespace V1\Controller;

class V1 extends \Controller
{
	/**
	 * Run this code before the other methods.
	 */
	public function before(){
		// Load the error strings.
		\Lang::load('v1::errors', true);
		\Lang::load('v1::log', true);
		\Lang::load('v1::response', true);
	}
	
	/**
	 * The main entry point for V1 of the API engine
	 * 
	 * @return string The JSON encoded string for the loader to parse
	 */
	public function action_index()
	{
		// Set up our APIRequest object
		if (is_array($setup = \V1\APIRequest::setup())) {
			return $this->response_format();
		}
		
		// Validate the request
		if (is_array($validate_request = \V1\ValidateRequest::run())) {
			return $this->response_format($validate_request);
		}
		
		// Validate a Data Call
		if (\V1\APIRequest::get('data-call', false) !== false) {
			
			if (is_array($data_call = \V1\Call\DataCall::run()) || is_string($data_call)) {
				
				// HTML only Data Calls
				if (
					\Session::get('response_format', false) === 'html' &&
					is_string($data_call)
				) {
					return $data_call;
				}
				
				// Non-HTML Data Calls
				return $this->response_format($data_call);
				
			}
			
		}
		
		// Are we processing an API call?
		if (\V1\APIRequest::get('api', false) !== false) {
			
			$runcall = new \V1\RunCall;
			if (is_array($response = $runcall->ignite())) {
				return $this->response_format($response);
			}
				
		}
		
		// Nothing happened, so return an error.
		return $this->response_format(\Utility::format_error(500));
	}
	
	/**
	 * Change an array to the proper structure for use with the loader
	 * 
	 * @param array $array The array of data to format
	 * @return mixed The restructured array or false on fail
	 */
	protected function response_format($array)
	{
		if (is_array($array)) {
				
			return array(
				
				$array,
				\Arr::get($array, 'errors.code', \Arr::get($array, 'code', 200))
		
			);
				
		}
		
		// When is an $array not an array? Now.
		return false;
	}
}

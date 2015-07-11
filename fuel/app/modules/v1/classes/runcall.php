<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Your runcle runs an API call on the remote server
 */

namespace V1;

class RunCall
{
	/**
	 * The \V1\APICall object
	 * @var \V1\APICall
	 */
	private $apicall_obj = null;
	
	/**
	 * The explosive device that functions as the entry point for the class
	 * 
	 * @return array The array of data from making the call, or the error array
	 */
	public function ignite()
	{
		// Validate the user data to see if any calls are possible
		if (is_array($validate = $this->validate())) {
			return $validate;
		}
		
		// Configure the call type
		if (is_array($configure = $this->configure())) {
			return $configure;
		}
		
		// Make the call
		if (is_array($response = $this->make_the_call($this->apicall_obj))) {
			return $response;
		}
		
		// Nothing happened, so return an error.
		return \Utility::format_error(500);
	}
	
	/**
	 * Validate the request for all calls.
	 * 
	 * @return mixed The response array, or boolean true if the request was successfully validated without a response
	 */
	public function validate()
	{
		if (is_array($validate_api_call = \V1\ValidateAPICall::run())) {
			return $validate_api_call;
		}
		
		return true;
	}
	
	/**
	 * Factory to configure various call types
	 * 
	 * @return mixed Boolean true on success or an error array
	 */
	public function configure()
	{
		if (\V1\APIRequest::is_static() === true) {
			
			if (is_array($call = \V1\Call\StaticCall::configure_call())) {
				return $call;
			}
			
		} else {
			
			if (is_array($call = \V1\Call\DynamicCall::configure_call())) {
				return $call;
			}
			
		}
		
		$this->apicall_obj = $call;
		return true;
	}
	
	/**
	 * Call the remote API server
	 * 
	 * @param \V1\APICall $apicall_obj The APICall object we're using to make calls
	 * @return array The array of data ready for display (Response array or error array)
	 */
	public function make_the_call(\V1\APICall $apicall_obj)
	{
		/**
		 * RUNCLE RICK'S RAD RUN CALL :)
		 */
		
		$api		= \V1\Model\APIs::get_api();
		$account	= \V1\Model\Account::get_account();
		
		/*
		 * When we make a call from the local server, we'll get the localhost IP. If that's the case,
		 * we'll set our public IP. DO NOT use X-Forwarded-For in the request headers to us. It's unreliable.
		 * We'll still set our X-Forwarded-For in case the API provider wishes to use it.
		 */
		$forwarded_for = \Input::real_ip('0.0.0.0', true);
		
		if ($internal_call = \Utility::is_internal_call()) {
			$forwarded_for = \Config::get('engine.call_test_ip');
		}
		
		/*
		 * Add our own headers to allow for authenticating our server and customers. We overwrite any
		 * of these headers that were specified by the API Provider through RAML, or by the developer
		 * through thier configuration.
		 */
		$headers = static::get_headers($apicall_obj->get_headers());
		
		$call = array(
		
			'url'		=> $apicall_obj->get_url(),
			'method'	=> $apicall_obj->get_method(),
			'headers'	=> $headers,
			'body'		=> $apicall_obj->get_method_params(),
			'body-type'	=> $apicall_obj->get_body_type(),
		
		);
		
		if (
			\Fuel::$env !== 'production' &&
			\Config::get('engine.dev_disable_live_calls', false) === true
		) {
		
			/**
			 * In dev mode we can disable calls to the remote server. Feel free to change the
			 * dummy response to whatever you'd like to.
			 */
			$response = array(
					
				'status'	=> 200,
				'headers'	=> array(
			
					'X-Dev-Mode'	=> 'Dummy header',
			
				),
				'body'	=> array(
		
					'dummy_key'	=> 'dummy_val',
		
				),
					
			);
			
			return \Utility::format_response(200, $response);
			
		} else {


			/*
			 * We'll see if anyone got a cached entry into our system while we were configuring stuff.
			 * That way we'll save time.
			 */
			if (\V1\APIRequest::is_static() && is_array($cached_data = \V1\Call\StaticCall::get_call_cache())) {
			
				// Return the response-formatted data from the cached entry.
				return $cached_data;
			
			}
			
			$queued = \V1\Socket::forge()->queue_call(\V1\APIRequest::get('api'), $call, $apicall_obj);
			
		}
		
		// Non-Data Calls grab the request right away.
		if (\Session::get('data_call', false) === false) {
			
			if ($queued === false) {
				
				// Server unavailable
				return \Utility::format_error(
					503,
					\Err::SERVER_ERROR,
					\Lang::get('v1::errors.remote_unavailable')
				);
				
			}
			
			// Pull the results.
			$result = \V1\Socket::forge()->get_results();
			
			if (is_array($result)) {
				
				// We only have one call.
				return $result[\V1\APIRequest::get('api')][0];
				
			} else {
				
				// If the request failed with false, it means that all streams timed out.
				return \Utility::format_error(500);
				
			}
			
		}
		
		$dc_response = array(
			
			'status'	=> 200,
			'headers'	=> array(),
			'body'		=> \V1\Constant::QUEUED_CALL,
			
		);
		
		// In Data Call mode we just signify that we've queued the call.
		return \Utility::format_response(200, $dc_response);
	}
	
	/**
	 * Add the array of Bit API Hub headers for the call
	 * 
	 * @param array $headers The array of existing headers
	 * @return array $headers with the Bit API Hub headers added on
	 */
	public static function get_headers(array $headers)
	{
		$api		= \V1\Model\APIs::get_api();
		$account	= \V1\Model\Account::get_account();
		
		$forwarded_for = \Input::real_ip('0.0.0.0', true);
		
		if ($internal_call = \Utility::is_internal_call()) {
			$forwarded_for = \Config::get('engine.call_test_ip');
		}
		
		$headers = array_replace($headers, array(
				
			'User-Agent'		=> 'API Optimization Engine/V1',
			'X-Forwarded-For'	=> $forwarded_for,
				
		));
		
		if (\Config::get('engine.send_engine_auth', false) === true) {
			
			// If the API hasn't yet received a secret identity, generate one.
			if (empty($api['secret'])) {
				$secret = \V1\Model\APIs::set_api_secret($api['id']);
			} else {
				$secret = \Crypt::decode($api['secret']);
			}
			
			$headers = array_replace($headers, array(
			
				'X-AOE-Secret'		=> $secret,
				'X-AOE-Account'		=> $account['id'],
				'X-AOE-Version'		=> 'V1',
			
			));
			
		}
		
		return $headers;
	}
}

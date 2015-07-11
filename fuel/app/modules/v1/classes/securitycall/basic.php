<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * HTTP Basic call object
 */

namespace V1\SecurityCall;

class Basic extends \V1\SecurityCall\Template\SecurityCallAbstract
{
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
	 * Run the security call and see what falls out.
	 * 
	 * @param \Raml\SecurityScheme $securityscheme_obj The security scheme to process the call data for
	 * @param \V1\APICall $apicall_obj					The APICall object
	 * 
	 * @return bool True on success, or false on fail
	 */
	public function run(\Raml\SecurityScheme $securityscheme_obj, \V1\APICall $apicall_obj)
	{
		/**
		 * @link https://en.wikipedia.org/wiki/Basic_access_authentication
		 */
		
		$credentials = $apicall_obj->get_credentials();
		
		// Make sure that we have the required data.
		if (
			empty($credentials['BASIC_USERNAME']) ||
			empty($credentials['BASIC_PASSWORD'])
		) {
			return false;
		}
		
		$encoded_credentials = 'Basic '.base64_encode(
			$credentials['BASIC_USERNAME'].':'.$credentials['BASIC_PASSWORD']
		);
		
		$apicall_obj->set_header('Authorization', $encoded_credentials);
		
		return true;
	}
}

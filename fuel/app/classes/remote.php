<?php
/**
 *  Copyright 2015 Bit API Hub
 *
 *  The class for remote operations (Mainly for logging)
 */

class Remote extends \Request
{
	/**
	 * Logging wrapper for off site requests (Allows for desired log formatting)
	 * 
	 * @param string $uri		The URL to execute
	 * @param string $options	The type of request
	 * @param string $method	The HTTP method to use for the request
	 */
	public static function forge($uri = NULL, $options = true, $method = NULL)
	{
		/*
		 * If someone uses this for internal calls, we'll still allow it to function, but we don't log it since it
		 * isn't a remote request.
		 */
		if (is_string($options)) {
			
			// We only grab the URL and method, not params or headers since the latter two could contain sensitive data.
			$tokens = array(
			
				'url'		=> $uri,
				'method'	=> $method,
			
			);
		
			// Log the request
			\Log::logger('INFO', 'REMOTE:'.$options, 'Started execution of an off-site request', __METHOD__, $tokens);
		}
		
		return parent::forge($uri, $options, $method);
	}
	
	/**
	 * Get the response from the remote server even when an error occurs.
	 * 
	 * @param object $request_obj The object to grab the response from
	 * @return object The \Response object
	 */
	public static function get_response($request_obj)
	{
		return \PrivatesPuller::get_response($request_obj);
	}
}

class PrivatesPuller extends \Request_Curl
{
	/**
	 * Now even exceptions bow before me. Muahahaha!
	 * 
	 * @param object $request_obj The object to grab the response from
	 * @return object The \Response object
	 */
	public static function get_response($request_obj)
	{
		return $request_obj->response;
	}
}

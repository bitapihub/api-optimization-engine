<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Abstract class for security call objects
 */

namespace V1\SecurityCall\Template;

abstract class SecurityCallAbstract implements \V1\SecurityCall\Template\SecurityCallInterface
{
	/**
	 * Process the request data to include the new headers.
	 *
	 * @param string $request The entire request to the remote server including headers and the body
	 * @return string The altered $request data
	 */
	public function before_send($request){
		return $request;
	}
	
	/**
	 * Touch up some settings after we've grabbed the response from the remote server.
	 *
	 * @param array $response The array of response data from \V1\Socket
	 * @return array The $response array with any needed alterations
	 */
	public function after_response(array $response){
		return $response;
	}
}

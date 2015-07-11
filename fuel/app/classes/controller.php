<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Simple extension for the \Fuel\Core\Controller
 */

abstract class Controller extends \Fuel\Core\Controller
{
	/**
	 * After every controller, change some things.
	 * 
	 * @param mixed The response for the call
	 * 
	 * @return object The \Response object
	 * @see \Fuel\Core\Controller::after()
	 */
	public function after($response)
	{
		// Make sure the $response is a Response object
		if ( ! $response instanceof Response)
		{
			$response = \Response::forge($response, $this->response_status);
		}
		
		return $response;
	}
}

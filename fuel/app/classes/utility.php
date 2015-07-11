<?php
/**
 *  Copyright 2015 Bit API Hub
 *
 *  Utility class for crap that just doesn't fit in anywhere else. (So descriptive)
 */

class Utility
{
	/**
	 * @var string $unique_user The unique ID for the user
	 * @access private
	 */
	private static $unique_user = null;
	
	/**
	 * A wrapper function to only cache things when we're not in development mode
	 *
	 * @param string $key		The name of the key to create
	 * @param mixed $value		The value for the key
	 * @param int|null $expiry	The expiry for the cached entry in seconds (Defaults to never expire)
	 */
	public static function set_cache($key, $value, $expiry = null)
	{
		if (!in_array(\Fuel::$env, array(\Fuel::DEVELOPMENT, 'private'))) {
			
			\Cache::set($key, $value, $expiry);
	
		}
	}
	
	/**
	 * Generate a random anonymous ID
	 * 
	 * @link https://gist.github.com/dahnielson/508447#file-uuid-php-L74
	 * 
	 * @return string Version 4 UUID - A pseudo-random string
	 */
	public static function generate_random_id()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
	
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
	
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
	
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
	
			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}
	
	/**
	 * Set a unique ID for the user, or grab the existing one. (For use in the logs)
	 * 
	 * We can't use sessions for this process as it creates an endless loop if the session ID is bad.
	 * (Session tries to get the cookie, finds out that it's a bad session, tries to log it, checks the
	 * unique user ID in the session, finds that it's a bad session, tries to log it...)
	 */
	public static function unique_user()
	{
		if (empty(static::$unique_user)) {
			static::$unique_user = static::generate_random_id();
		}
		
		return static::$unique_user;
	}
	
	/**
	 * Format an error message
	 * 
	 * @param int $status_code	The HTTP status code
	 * @param string $message	Set a custom message for display, or leave it empty to show a generic error message.
	 * 
	 * @return array The error message array
	 */
	public static function format_error($status_code, $error_type = null, $message = null)
	{
		\Lang::load('errors', 'errors');
		
	    // Figure out what type of error it is.
	    if (empty($error_type)) {
	    	
	    	$error_type = \Err::UNKNOWN_ERROR;
	    	
	    	if ($status_code < 500) {
	    		$error_type = \Err::CLIENT_ERROR;
	    	}
	    	 
	    	if ($status_code < 400) {
	    		$error_type = \Err::REDIRECT;
	    	}
	    	 
	    	if ($status_code < 300 || $status_code >= 500) {
	    		$error_type = \Err::SERVER_ERROR;
	    	}
	    	
	    }
	    
	    if (empty($message)) {
	        $message = \Lang::get('errors.'.$status_code, array(), \Lang::get('errors.default'));
	    }
	    
	    // Slap it all together and call it silly.
	    return array(
	    
	    	'errors'	=> array(
	    	
	    		'code'		=> $status_code,
	    		'type'		=> $error_type,
	    		'message'	=> $message,
	    		
	    	),
	    
	    );
	}
	
	/**
	 * Format the response data for API calls
	 * 
	 * @param int $status_code	The HTTP status code for the call
	 * @param array $response	The array of response data for the call
	 * @param int $ts			The timestamp for when the response was originally received by the API engine -
	 * 							The default is to create a new timestamp.
	 */
	public static function format_response($status_code, $response, $ts = null)
	{
		return array(
			 
			'code'		=> $status_code,
			'ts'		=> empty($ts) ? time() : $ts,
			'response'	=> $response,
			 
		);
		
	}
	
	/**
	 * Subtract an amount of time from a timestamp without extravagant and arcane DateTime class crap
	 * 
	 * @param string $time_period	Set to second, minute, hour, day, month, or year
	 * @return int The timestamp less the number of seconds for the $time_period
	 */
	public static function subtract_seconds($time_period)
	{
		$dt = new DateTime('-1 '.$time_period, new DateTimezone(\Config::get('default_timezone')));
		
		return $dt->getTimestamp();
	}
	
	/**
	 * Manually parse the response into the proper format
	 * 
	 * @param array $response_array The array of response data
	 * @return string The parsed response in the desired format
	 */
	public static function prepare_response(array $response_array)
	{
		$format = \Session::get('response_format', 'json');
		
		// Make sure we can create it in that format
		if ($format === 'array' || !method_exists('Format', 'to_'.$format)) {
			$format = 'json';
		}
		
		// XML needs the REST basenode from the config, not the one from the \Format config.
		if ($format === 'xml') {
			return \Format::forge($response_array)->{'to_'.$format}(null, null, \Config::get('rest.xml_basenode', 'xml'));
		}
		
		// Anything other than XML
		return \Format::forge($response_array)->{'to_'.$format}();
	}
	
	/**
	 * Return the array of mime types for a given format, or the format for a given mime-type.
	 * 
	 * @param string $format The desired format or mime-type to pull data for
	 * @return mixed The format as a string, an array of mime types, or false if nothing was found
	 */
	public static function get_format($format)
	{
		\Config::load('engine', true);
		
		foreach (\Config::get('engine.supported_formats', array()) as $format_name => $mime_types) {
			
			// Matches the format
			if ($format === $format_name) {
				return $mime_types;
			}
			
			// Matches a mime type
			if (in_array($format, $mime_types)) {
				return $format_name;
			}
			
		}
		
		// Couldn't find anything
		return false;
	}
	
   /**
	* This program is free software. It comes without any warranty, to
	* the extent permitted by applicable law. You can redistribute it
	* and/or modify it under the terms of the Do What The Fuck You Want
	* To Public License, Version 2, as published by Sam Hocevar. See
	* http://sam.zoy.org/wtfpl/COPYING for more details.
	*/
	
   /**
	* Tests if an input is valid PHP serialized string.
	*
	* Checks if a string is serialized using quick string manipulation
	* to throw out obviously incorrect strings. Unserialize is then run
	* on the string to perform the final verification.
	*
	* Valid serialized forms are the following:
	* <ul>
	* <li>boolean: <code>b:1;</code></li>
	* <li>integer: <code>i:1;</code></li>
	* <li>double: <code>d:0.2;</code></li>
	* <li>string: <code>s:4:"test";</code></li>
	* <li>array: <code>a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}</code></li>
	* <li>object: <code>O:8:"stdClass":0:{}</code></li>
	* <li>null: <code>N;</code></li>
	* </ul>
	*
	* @author		Chris Smith <code+php@chris.cs278.org>
	* @copyright	Copyright (c) 2009 Chris Smith (http://www.cs278.org/)
	* @license		http://sam.zoy.org/wtfpl/ WTFPL
	* @link			https://gist.github.com/cs278/217091
	* @param		string	$value	Value to test for serialized form
	* @return		boolean			True if $value is serialized data, otherwise false
	*/
	public static function is_serialized($value)
	{
		// Bit of a give away this one
		if (!is_string($value) || empty($value) || substr_count($value, ':') === 0)
		{
			return false;
		}
	
		// Serialized false, return true. unserialize() returns false on an
		// invalid string or it could return false if the string is serialized
		// false, eliminate that possibility.
		if ($value === 'b:0;')
		{
			return true;
		}
	
		$length	= strlen($value);
		$end	= '';
	
		switch ($value[0])
		{
			case 's':
				if ($value[$length - 2] !== '"')
				{
					return false;
				}
			case 'b':
			case 'i':
			case 'd':
				// This looks odd but it is quicker than isset()ing
				$end .= ';';
			case 'a':
			case 'O':
				$end .= '}';
	
				if ($value[1] !== ':')
				{
					return false;
				}
	
				switch ($value[2])
				{
					case 0:
					case 1:
					case 2:
					case 3:
					case 4:
					case 5:
					case 6:
					case 7:
					case 8:
					case 9:
						break;
	
					default:
						return false;
				}
			case 'N':
				$end .= ';';
	
				if ($value[$length - 1] !== $end[0])
				{
					return false;
				}
				break;
	
			default:
				return false;
		}
		
		return true;
	}
	
	/**
	 * 
	 *                                                 ,w.
                                              ,YWMMw  ,M  ,
                         _.---.._   __..---._.'MMMMMw,wMWmW,
                    _.-""        """           YP"WMMMMMMMMMb,
                 .-' __.'                   .'     MMMMW^WMMMM;
     _,        .'.-'"; `,       /`     .--""      :MMM[==MWMW^;
  ,mM^"     ,-'.'   /   ;      ;      /   ,       MMMMb_wMW"  @\
 ,MM:.    .'.-'   .'     ;     `\    ;     `,     MMMMMMMW `"=./`-,
 WMMm__,-'.'     /      _.\      F"""-+,,   ;_,_.dMMMMMMMM[,_ / `=_}
 "^MP__.-'    ,-' _.--""   `-,   ;       \  ; ;MMMMMMMMMMW^``; __|
            /   .'            ; ;         )  )`{  \ `"^W^`,   \  :
           /  .'             /  (       .'  /     Ww._     `.  `"
          /  Y,              `,  `-,=,_{   ;      MMMP`""-,  `-._.-,
 fsc     (--, )                `,_ / `) \/"")      ^"      `-, -;"\:
          `"""                    `"""   `"'                  `---" 
          
	 * Credit: @link http://www.ascii-art.de/ascii/jkl/lion.txt
	 *
	 */
	
	/**
	 * Check if the API engine is getting called from an internal sourcellion.
	 * 
	 * @return bool True if we're calling from inside, or false if not
	 */
	public static function is_internal_call()
	{
		return \Input::real_ip('0.0.0.0', true) === '0.0.0.0';
	}
	
	/**
	 * Get a nonce for all of our noncing purposes. :)
	 * 
	 * @return string The hexidecimal nonce
	 */
	public static function get_nonce()
	{
		return hash('sha256', openssl_random_pseudo_bytes(32));
	}
}

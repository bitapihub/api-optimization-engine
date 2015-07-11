<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * API request data object
 */

namespace V1;

class APIRequest
{
	/**
	 * The API request settings
	 * @var array
	 */
	protected $settings = array();
	
	/**
	 * The object instance
	 * @var object
	 */
	private static $instance = null;
	
	/**
	 * The original post data from the client
	 * @var array
	 */
	private $post_data = array();
	
	/**
	 * The array of data posted for the Data Call
	 * @var array
	 */
	private $data_call	= array();
	
	/**
	 * The instance singleton
	 * 
	 * @param string $instance The name of the instance
	 * @return object The instance of the object
	 */
	public static function instance()
	{
		if (empty(static::$instance)) {
			static::$instance = new static;
		}
		
		return static::$instance;
	}
	
	/**
	 * Get a settings value from the Data Call.
	 * 
	 * @param string $key		The name of the key to grab (Dot notation supported) Leave it null to get
	 * 							the whole array
	 * @param mixed $default	The default value to return
	 * 
	 * @return mixed The settings value or the default value
	 */
	public static function data_call($key = null, $default = false)
	{
		return \Arr::get(static::instance()->data_call, $key, $default);
	}
	
	/**
	 * Get a settings value.
	 * 
	 * @param string $key		The name of the key to grab (Dot notation supported) Leave it null to get
	 * 							the whole array
	 * @param mixed $default	The default value to return
	 * 
	 * @return mixed The settings value or the default value
	 */
	public static function get($key = null, $default = false)
	{
		return \Arr::get(static::instance()->settings, $key, $default);
	}
	
	/**
	 * Set a settings value.
	 * 
	 * @param string $key	The name of the key to grab (Dot notation supported)
	 * @param mixed $value	The value to set on the key
	 */
	public static function set($key, $value)
	{
		\Arr::set(static::instance()->settings, $key, $value);
	}
	
	/**
	 * Populate the settings data.
	 * 
	 * @return mixed The error array if we submitted serialized data, or void if everything went as planned.
	 */
	public static function setup()
	{
		$post_data = \Session::get('data_call', false) === false ? \Input::post() : \Session::get('posted_data', array());
		
		$settings_array = array();
		foreach ($post_data as $input_key => $input_val) {
			
			$decoded = is_string($input_val) ? json_decode($input_val, true) : $input_val;
			$input_val = empty($decoded) ? $input_val : $decoded;
			
			// No serialized input due to security issues
			if (static::is_serialized_recursive($input_val)) {
				return \Utility::format_error(400);
			}
			
			$settings_array[$input_key] = $input_val;
			
		}
		
		if (isset($post_data['data-call'])) {
			static::instance()->data_call = $settings_array;
		}
		
		static::instance()->settings = $settings_array;
		static::instance()->post_data = $post_data;
		return true;
	}
	
	/**
	 * Check if we're making a static call.
	 * 
	 * @return boolean True if we're making a static call, or false if we aren't
	 */
	public static function is_static()
	{
		return array_key_exists('static-call', static::instance()->settings);
	}
	
	/**
	 * Grab the original unaltered post data
	 * 
	 * @return array The array of post data (Usually \Input::post())
	 */
	public static function post_data()
	{
		return static::instance()->post_data;
	}
	
	
	
	
	
	/**
	 * PROTECTED PARTS
	 */
	
	/**
	 * Loop and check to see if any values in an array are serialized data.
	 * 
	 * @param mixed $checkme	The data to check (string or array)
	 * @param int $levels_deep	How deep should our recursion be?
	 * 
	 * @return boolean True if there was a serialized value, or false if there wasn't
	 */
	protected static function is_serialized_recursive($checkme, $levels_deep = 1)
	{
		// Prevent further recursion and assume all is well.
		if ($levels_deep === 0) {
			return false;
		}
		
		// Strings
		if (is_string($checkme)) {
			return \Utility::is_serialized($checkme);
		}
		
		// Arrays
		if (is_array($checkme)) {
			
			foreach ($checkme as $key => $value) {
				return static::is_serialized_recursive($value, $levels_deep-1);
			}
			
		}
		
		// Not serialized
		return false;
	}
}

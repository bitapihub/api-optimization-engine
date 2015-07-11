<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * RAML YAML ding dong (Parses RAML and YAML)
 */

namespace V1;

class RAML
{
	/**
	 * Parse a RAML file
	 * 
	 * @param string $raml_string	The RAML string to parse
	 * @param string $root_dir		The root directory to parse !include files (Defaults to the "raml"
	 * 								directory for the module.)
	 * @param bool $parse_schemas	True to parse included RAML files, or false not to (Defaults to true)
	 * 
	 * @return mixed The \Raml\APIDefinition object, or false if the RAML string didn't exist
	 */
	public static function parse($raml_string = null, $root_dir = null, $parse_schemas = true)
	{
		// Find the RAML string.
		if (empty($raml_string) && ($raml_string = static::get_raml_string()) === false) {
			return false;
		}
		
		// Use the default root directory for RAML files.
		if (empty($root_dir)) {
			$root_dir = __DIR__.DS.'..'.DS.'raml';
		}
		
		// Build the settings object.
		$parser_config_obj = new \Raml\ParseConfiguration();
		
		if ($parse_schemas === true) {
			$parser_config_obj->enableSchemaParsing();
		} else {
			$parser_config_obj->disableSchemaParsing();
		}
		
		/*
		 * Don't create security settings parser objects. They restrict settings to only those the parsers
		 * specify.
		 */
		$parser_config_obj->disableSecuritySchemeParsing();
		
		$parser = new \Raml\Parser(null, null, null, $parser_config_obj);
		
		/**
		 * Prevent the parser from automatically merging security scheme data with the method.
		 * 
		 * @link https://github.com/alecsammon/php-raml-parser/issues/68
		 */
		//$parser->setMergeSecurity();
		
		try {
			$api_def = $parser->parseFromString($raml_string, $root_dir);
		} catch (\Raml\Exception\InvalidSchemaTypeException $e) {
			return \Utility::format_error(500, \V1\Err::INVALID_SCHEMA_TYPE, \Lang::get('v1::errors.invalid_schema_type'));
		}
		
		return $api_def;
	}
	
	/**
	 * Return the array of static call data if it exists.
	 * 
	 * @param string $raml_string The RAML string to parse or null to parse the RAML data for the posted API
	 * @return mixed The array of static calls if located, or false if they don't exist
	 */
	public static function parse_static_calls($raml_string = null)
	{
		return static::parse_yaml($raml_string, '/{{static-calls}}');
	}
	
	/**
	 * Return the array of dynamic call data if it exists.
	 * 
	 * @param string $raml_string The RAML string to parse or null to parse the RAML data for the posted API
	 * @return mixed The array of package limits if located, or false if they don't exist
	 */
	public static function parse_package_limits($raml_string = null)
	{
		return static::parse_yaml($raml_string, 'limits');
	}
	
	/**
	 * PROTECTED PARTS
	 */
	
	/**
	 * Parse a YAML string into an array
	 * 
	 * @param string $yaml_string	The YAML string to parse or null to parse the RAML data for the posted API
	 * @param string $key			Set the key to return from the parsed array, or leave it empty to
	 * 								return everything.
	 * @param boolean $from_cache	Set to true to pull valid records from cache, or false not to. (Cached for
	 * 								the default cache period)
	 * @return mixed The array of data requested, or boolean false if the key didn't exist
	 */
	protected static function parse_yaml($yaml_string = null, $key = null, $from_cache = true)
	{
		// Find the YAML string.
		if (empty($yaml_string) && ($yaml_string = static::get_raml_string()) === false) {
			return false;
		}
	
		// It must be a string.
		if (!is_string($yaml_string)) {
			return false;
		}
		
		// Try to pull it from cache to conserve resources if we aren't in dev mode.
		if ($from_cache === true) {
			
			try {
				$yaml_array = \Cache::get('yaml.'.sha1($yaml_string));
			} catch (\CacheNotFoundException $e) {
				$from_cache = false;
			}
			
		}
		
		// If we aren't able to pull from the cache, then parse it.
		if ($from_cache === false || \Fuel::$env === 'private') {
			
			$yaml_array = \Symfony\Component\Yaml\Yaml::parse($yaml_string);
			
			/*
			 * Yaml throws exceptions when something messes up. We don't catch the exception so as to
			 * maintain system integrity. If an exception is thrown, we won't cache the crap data.
			 */
			
			\Cache::set('yaml.'.sha1($yaml_string), $yaml_array);
			
		}
		
		// If we are requesting a key from the array...
		if (!empty($key)) {
			
			// Return the specified array if it exists.
			if (isset($yaml_array[$key])) {
				return $yaml_array[$key];
			}
			
			// A key was specified, but it didn't exist in the parsed data.
			return false;
			
		}
		
		// No key specified, so return the full array.
		return $yaml_array;
	}
	
	/**
	 * Locate the RAML string for the current API
	 * 
	 * @return mixed The RAML string, or boolean false if we couldn't locate it.
	 */
	protected static function get_raml_string()
	{
		$api_data = \V1\Model\APIs::get_api();
		return empty($api_data['raml']) ? false : $api_data['raml'];
	}
}

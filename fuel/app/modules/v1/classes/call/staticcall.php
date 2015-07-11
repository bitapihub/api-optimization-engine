<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Class for working with static API calls
 */

namespace V1\Call;

class StaticCall
{
	/**
	 * Grab the static call cache
	 * 
	 * @param string $api		The name of the API to grab data for, or empty for the posted value
	 * @param string $api_call	The static API call to grab the cached data for, or leave it empty for the posted value
	 * 
	 * @return mixed The array of static call data from the cache, or boolean false if we don't have an entry
	 */
	public static function get_call_cache($api = null, $api_call = null)
	{
		$api		= empty($api) ? \V1\APIRequest::get('api') : $api;
		$api_call	= empty($api_call) ? \V1\APIRequest::get('static-call') : $api_call;
		
		// We need proper values, and we don't use the cache for internal calls.
		if (empty($api) || empty($api_call) || \Utility::is_internal_call() === true) {
			return false;
		}
		
		/*
		 * When we set a new cached entry, we set the cache to timeout at a specified time, so we don't check
		 * to see if it's valid now.
		 */
		try {
			
			$api_data = \V1\Model\APIs::get_api($api);
			if ($api_data['account_id'] !== 0) {
				
				// Private cache
				return \Cache::get('private_static_calls.'.$api_data['account_id'].'.'.$api.'.'.$api_call);
				
			} else {
				
				// Public cache
				return \Cache::get('static_calls.'.$api.'.'.$api_call);
				
			}
			
		} catch (\CacheNotFoundException $e) {
			return false;
		}
	}
	
	/**
	 * Cache a static call
	 * 
	 * @param array $response	The response data from @See \Utility::format_response()
	 * @param mixed $duration	The number of seconds to cache the call for
	 * @param string $api		The API to cache the data for, or empty for the posted value
	 * @param string $api_call	The API call to cache, or empty to use the posted value
	 * 
	 * @return boolean True on success, false if we're missing $api or $api_call values
	 */
	public static function set_call_cache(array $response, $api = null, $api_call = null, $duration = 900)
	{
		$api		= empty($api) ? \V1\APIRequest::get('api') : $api;
		$api_call	= empty($api_call) ? \V1\APIRequest::get('static-call') : $api_call;
		
		// We need proper values, and we don't use the cache for internal calls.
		if (empty($api) || empty($api_call) || \Utility::is_internal_call() === true) {
			return false;
		}
		
		// If call caching is disabled, then don't cache the response.
		if (\Config::get('engine.cache_static_calls', true) === false) {
			return true;
		}
		
		$api_data = \V1\Model\APIs::get_api($api);
		if ($api_data['account_id'] !== 0) {
			
			// Private cache
			\Cache::set('private_static_calls.'.$api_data['account_id'].'.'.$api.'.'.$api_call, $response, $duration);
			
		} else {
			
			// Public cache
			\Cache::set('static_calls.'.$api.'.'.$api_call, $response, $duration);
			
		}
		
		return true;
	}
	
	/**
	 * Configure a static call
	 * 
	 * @return mixed The \V1\APICall object or an error array if the call wasn't found
	 */
	public static function configure_call()
	{
		if (is_array($static_calls = \V1\RAML::parse_static_calls())) {
		
			$api_data		= \V1\Model\APIs::get_api();
			$account_data	= \V1\Model\Account::get_account();
				
			foreach ($static_calls as $call => $call_data) {
		
				// The static call data matches the request.
				if ('/'.\V1\APIRequest::get('static-call') === $call) {
						
					// If we can't run the inactive call, then error out.
					if (
						isset($call_data['stability']) &&
						$call_data['stability'] === 0 &&
						(
							$account_data['can_run_inactive'] === 0 ||
							$account_data['id'] !== $api_data['account_id']
						)
					) {
						return \Utility::format_error(403, \V1\Err::DISABLED_STATIC, \Lang::get('v1::errors.disabled_static'));
					}
						
					// Do we have a cached entry?
					if (is_array($cached_data = \V1\Call\StaticCall::get_call_cache())) {
		
						// Set their usage stats.
						\V1\Usage::set_usage();
		
						// Set the API provider stats
						\V1\Usage::set_api_stats($cached_data['response']['status']);
		
						// Return the response-formatted data from the cached entry.
						return $cached_data;
		
					}
						
					// Try to get an APICall object
					if (($apicall = static::apicall_object($call)) !== false) {
						return $apicall;
					}
		
				}
		
			}
		
			// The static call wasn't found.
			return \Utility::format_error(404, \V1\Err::BAD_STATIC, \Lang::get('v1::errors.bad_static'));
		
		} else {
		
			// If they've requested a static call when there aren't any for the requested API, give 'em errors!
			return \Utility::format_error(404, \V1\Err::NO_STATIC, \Lang::get('v1::errors.no_static'));
		
		}
	}
	
	/**
	 * Try to get an \APICall object
	 *
	 * @param string $call The static call name, or null if we're making a dynamic call.
	 * @return mixed The \APICall object on success, or false if an error occurred.
	 */
	protected static function apicall_object($call = null)
	{
		// For some reason we can't parse the RAML, so we throw a 500 error.
		if (($api_def = \V1\RAML::parse()) === false) {
			return \Utility::format_error(500);
		} elseif (is_array($api_def)) {
	
			// Specific error
			return $api_def;
	
		}
	
		$all_calls = (array) $api_def->getResourcesAsUri();
		$all_calls = reset($all_calls);
		
		$api_call = null;
		
		// Loop through every possible URI on the API
		foreach ($all_calls as $uri => $call_data) {
			
			// GET /res/name
			$uri_explode = explode(' ', $uri);
			
			// Is it the static call we need?
			if (($call_uri = str_replace('/{{static-calls}}'.$call, '', $uri_explode[1])) !== $uri_explode[1]) {
				
				/*
				 * Static calls only have one method, so since it matches the resource, we'll pass along
				 * the method it uses.
				 */
				$api_call = \V1\APICall::forge($api_def, $uri_explode[0], $call_uri, $uri_explode[1]);
				break;
				
			}
			
		}
	
		if (is_object($api_call)) {
			
			if (!empty($api_call->get_errors())) {
			
				// Errors from \APICall
				return $api_call->get_errors();
			
			} else {
			
				// Return the \APICall object
				return $api_call;
			
			}
			
		}
		
		return false;
	}
}

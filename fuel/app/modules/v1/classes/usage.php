<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * API usage handler
 */

namespace V1;

class Usage
{
	/**
	 * Get the usage statistics for the chosen account
	 * 
	 * @param mixed $api The name of the API to retrieve stats for, boolean false to pull the
	 * 						account total, or empty to get everything
	 * @return array The array of usage statistics for the account
	 */
	public static function get_usage($api = null)
	{
		try {
			
			// Grab the stats
			$usage = \Cache::get('account.'.\Session::get('consumer_key').'usage');
			
			$account_data = \V1\Model\Account::get_account();
			
			/*
			 * Reset their usage statistics when their package is set to do so. When the account
			 * holder makes a payment, the reset time gets set to the current time so that the
			 * account can gain access to their new stats right away. Free accounts set the new
			 * time to 30 days after a reset. (Configured in the "tiers" config.)
			 */
			if (!empty($account_data['reset_usage']) && $account_data['reset_usage'] <= time()) {
				
				static::delete_usage();
				return array();
				
			}
			
			// Pull the total API calls for the account
			if ($api === false) {
				return isset($usage['total']) ? $usage['total'] : 0;
			}
			
			// Pull all stats
			if (empty($api)) {
				return $usage;
			}
			
			// Pull stats for a certain API
			if (isset($api)) {
				
				if (isset($usage[$api])) {
					return $usage[$api];
				}
				
				$new_entry = array_fill_keys(
					array('second', 'minute', 'hour', 'day', 'month', 'year'),
					array(
				
						'ts'	=> time(),
						'count'	=> 0,
							
					)
				);
				
				return $new_entry;
				
			}
			
			return array();
			
		} catch (\CacheNotFoundException $e) {
			return array();
		}
	}
	
	/**
	 * Set the API usage stats for the account
	 *  
	 * @param string $api The name of the API to set the usage statistics for or null for the current API
	 */
	public static function set_usage($api = null)
	{
		if (empty($api)) {
			$api = \V1\APIRequest::get('api');
		}
		
		// Default
		$usage = array();
		
		// Grab any current stats
		try {
			$usage = \Cache::get('account.'.\Session::get('consumer_key').'usage');
		} catch (\CacheNotFoundException $e) {}
		
		// Every time period starts with this data
		$reset_array = array(
		
			'ts'	=> time(),
			'count'	=> 1,
				
		);
		
		// Excrement the total calls through the system.
		if (empty($usage['total'])) {
			$usage['total'] = 1;
		} else {
			$usage['total']++;
		}
		
		/**
		 * Increment the API's call counter
		 */
		
		// No data exists.
		if (empty($usage[$api])) {
			
			// Set each time period to the starting data.
			$usage[$api] = array_fill_keys(array('second', 'minute', 'hour', 'day', 'month', 'year'), $reset_array);
			
		// Data exists in the system.
		} else {
			
			// Loop through the time period data
			foreach ($usage[$api] as $time_period => $period_data) {
				
				// If the timestamp is still valid, then increment the counter.
				if (
					\Utility::subtract_seconds($time_period) <= $period_data['ts'] &&
					$period_data['ts'] <= time() // DST adjustments
				) {
					$usage[$api][$time_period]['count']++;
				} else {
					
					// The timestamp was invalid, so we reset the time period.
					$usage[$api][$time_period] = $reset_array;
					
				}
				
			}
			
		}
		
		// Set it to the new value, and make sure that it doesn't expire.
		\Cache::set('account.'.\Session::get('consumer_key').'usage', $usage, null);
	}
	
	/**
	 * Delete all usage statistics, or the usage statistics for an API.
	 * 
	 * @param string $api The API to delete the usage statistics for, or leave it empty to remove everything
	 */
	public static function delete_usage($api = null)
	{
		// Delete everything
		if (empty($api)) {
			
			\Cache::delete('account.'.\Session::get('consumer_key').'usage');
			
			/*
			 * Remove the reset time from the DB so that we aren't always resetting it.
			 * Free account don't pay (obviously), so we set their reset timer to 30 days
			 * from now.
			 */
			$account_data = \V1\Model\Account::get_account();
			
			$time = null;
			if ($account_data['access_level'] === 1) {
				$time = time() + ((int) \Config::get('tiers.free.reset_period', 30) * 24 * 60 * 60);
			}
			\Debug::dump($time);
			\V1\Model\Account::set_reset_usage($time);
			
		} else {
			
			try {
				
				// If we have an entry for the API, then delete it.
				$usage = \Cache::get('account.'.\Session::get('consumer_key').'usage');
				if (isset($usage[$api])) {
					unset($usage[$api]);
				}
				
				// Set it without the value for the chosen API.
				\Cache::set('account.'.\Session::get('consumer_key').'usage', $usage);
				
			} catch (\CacheNotFoundException $e) {}
			
		}
	}
	
	/**
	 * Set the usage stats for an API call. API Providers use these for their statistics.
	 * 
	 * @param number $status			The HTTP status code from the remote server
	 * @param mixed $api_request_data	The array of API request data, or null to use \V1\APIRequest::get()
	 * @param mixed $is_static			True or false to signify static status, or null to use \V1\APIRequest::is_static()
	 */
	public static function set_api_stats($status = 200, $api_request_data = null, $is_static = null)
	{
		$api_request_data	= $api_request_data === null ? \V1\APIRequest::get() : $api_request_data;
		$is_static			= $is_static === null ? \V1\APIRequest::is_static() : $is_static;
		
		if ($is_static === true) {
			\V1\Model\APIStats::set_stat($status, $api_request_data['static-call'], $api_request_data['api'], $is_static);
		} else {
			\V1\Model\APIStats::set_stat($status, $api_request_data['configure']['uri'], $api_request_data['api'], $is_static);
		}
	}
}

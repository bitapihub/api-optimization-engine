<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Validate a call to an API
 */

namespace V1;

class ValidateAPICall
{
	/**
	 * Validate the call to the chosen API
	 * 
	 * @return mixed The response array, or boolean true if the request was successfully validated without a response
	 */
	public static function run()
	{
		/**
		 * Access to the "usage" API
		 */
		if (is_array($usage = static::usage())) {
			return $usage;
		}
		
		// Pull the row of API data from the DB, and grab the account data
		$api_data		= \V1\Model\APIs::get_api(\V1\APIRequest::get('api'));
		$account_data	= \V1\Model\Account::get_account();
		
		/**
		 * Verify that the API exists
		 */
		if (empty($api_data)) {
			return \Utility::format_error(404, \V1\Err::INVALID_API_NAME, \Lang::get('v1::errors.invalid_api_name'));
		}
		
		/**
		 * Is the API active? Can we run it?
		 */
		
		/*
		 * If the API is disabled, and it's not part of the account running the call, and they don't have
		 * permission to run disabled APIs, then block the request.
		 */
		if ($api_data['active_level'] === 0) {
			
			if (
				$account_data['can_run_inactive'] === 0 ||
				$account_data['id'] !== $api_data['account_id']
			) {
				
				// We can't run disabled calls on this API.
				return \Utility::format_error(403, \V1\Err::DISABLED_API, \Lang::get('v1::errors.disabled_api'));
				
			}
			
		}
		
		/**
		 * Is the API accessible to the plan that the account is on?
		 */
		if (
			$account_data['access_level'] < $api_data['min_access_level'] ||
			(\V1\APIRequest::is_static() === false && $account_data['access_level'] === 1)
		) {
			return \Utility::format_error(402, \V1\Err::UPGRADE_REQUIRED, \Lang::get('v1::errors.upgrade_required'));
		}
		
		/**
		 * Are we speeding?
		 */
		if (is_array($need_for_speed = static::check_usage_limits())) {
			return $need_for_speed;
		}
		
		// No problemo, man!
		return true;
	}
	
	/**
	 * Report usage statistics to the client
	 * 
	 * @return mixed The response array for the client, or false if the request wasn't for the usage API
	 */
	private static function usage()
	{
		// Is the requested API the usage API?
		if (\V1\APIRequest::get('api') === 'usage') {
		
			// Public calls may not access the usage API
			if (\Session::get('public', true) === false) {
				return \Utility::format_response(200, \V1\Usage::get_usage());
			}
			
			// It was a public call to the usage API, so block it.
			return \Utility::format_error(403, \V1\Err::NOT_PUBLIC, \Lang::get('v1::errors.not_public'));
			
		}
		
		return false;
	}
	
	/**
	 * Check the speed limits of an API to make sure that the account isn't calling the API more than it's
	 * allowed to in the specified time frame.
	 * 
	 * @return mixed True if the call is within the speed limits, or the error array if it isn't
	 */
	private static function check_usage_limits()
	{
		$api_data 		= \V1\Model\APIs::get_api(\V1\APIRequest::get('api'));
		$account_data	= \V1\Model\Account::get_account();
		
		$package_limits = \V1\RAML::parse_package_limits();
		
		/*
		 * Are there any limits on the API for our package level? API limits are imposed
		 * by Bit API Hub, not the API provider. It helps to limit the amount of bandwidth
		 * we allot for each call. The heavier the call, the less calls people can make.
		 */
		if (
			is_array($package_limits) &&
			array_key_exists('level'.$account_data['access_level'], $package_limits) &&
			!empty($usage = \V1\Usage::get_usage(\V1\APIRequest::get('api')))
		) {
			
			// Loop the allotments of API calls per time period
			foreach ($package_limits['level'.$account_data['access_level']] as $time_period => $allotment) {
				
				// If we have a valid log for the time period, and it's already maxed out, fail the check.
				if (
					isset($usage[$time_period]) &&
					$usage[$time_period]['count'] == $allotment &&
					\Utility::subtract_seconds($time_period) <= $usage[$time_period]['ts'] &&
					$usage[$time_period]['ts'] <= time() // DST adjustments
				) {
					return \Utility::format_error(
						429,
						\V1\Err::TOO_MANY_REQUESTS,
						\Lang::get('v1::errors.too_many_requests', array(
							
							'allotment'	=> number_format($allotment),
							'period'	=> $time_period,
						
						)));
				}
				
			}
			
		}
		
		// We're not breaking the speed limit, so shush.
		return true;
	}
}

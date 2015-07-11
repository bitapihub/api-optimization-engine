<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Validate an API call before processing the call.
 */

namespace V1;

class ValidateRequest
{
	/**
	 * Validate the request to make sure we have trusted and sufficient data.
	 * 
	 * @return mixed True on success, or an array of error data on fail
	 */
	public static function run()
	{
		/**
		 * Verify that we have at least an "api" or "data-call" token.
		 */
		if (\V1\APIRequest::get('api', false) === false && \V1\APIRequest::get('data-call', false) === false) {
			return  \Utility::format_error(400, \V1\Err::BAD_BODY, \Lang::get('v1::errors.bad_body'));
		}
		
		/**
		 * Easter egg processing plant
		 */
		if (\V1\APIRequest::get('api') === 'I\'m a teapot') {
			return \Utility::format_error(418, \V1\Err::IM_A_TEAPOT, str_replace("\t", '', \Lang::get('v1::errors.im_a_teapot')));
		}
		
		/**
		 * AUTHORIZATION
		 */
		
		// Once we've authenticated to start running calls from one Data Call, we don't authenticate again.
		if (\Session::get('data_call', false) === false) {
			
			// If they failed to authenticate, then issue a 401 unauthorized error.
			if (\V1\Account::authenticate() === false) {
					
				// Log the failure.
				\Log::logger(
					'INFO',
					'AUTHORIZE:FAIL',
					\Lang::get('log.authorize_fail'),
					__METHOD__,
					array(
						'consumer_key'	=> \Session::get('consumer_key', 'NOT SET'),
						'public_mode'	=> \Session::get('public', 'NOT SET'),
					)
				);
					
				return  \Utility::format_error(401);
					
			}
			
			// Log the success.
			\Log::logger(
				'INFO',
				'AUTHORIZE:SUCCESS',
				\Lang::get('log.authorize_success'),
				__METHOD__,
				array(
					'consumer_key'	=> \Session::get('consumer_key', 'NOT SET'),
					'public_mode'	=> \Session::get('public', 'NOT SET'),
				)
			);
			
			/**
			 * DOWNGRADE PROCESSING
			*/
			\V1\Account::downgrade();
			
		}
		
		/**
		 * GLOBAL LIMITS
		 */
		if (static::check_global_limits() === false) {
			return \Utility::format_error(429, \V1\Err::MAXED_OUT_LIMITS, \Lang::get('v1::errors.maxed_out_limits'));
		}
		
		return true;
	}
	
	/**
	 * PROTECTED BITS
	 */
	
	/**
	 * Check if the account has reached the maximum number of API calls it can make globally (not API specific)
	 * 
	 * @return boolean True if they're within their limits, false if not
	 */
	protected static function check_global_limits()
	{
		$account_data	= \V1\Model\Account::get_account();
		$total_calls	= \V1\Usage::get_usage(false);
		
		// If they've used up all of their calls, and they aren't unlimited, then they've hit their global limits.
		if ($total_calls === $account_data['max_calls'] && $account_data['max_calls'] != 0) {
			return false;
		}
		
		return true;
	}
}

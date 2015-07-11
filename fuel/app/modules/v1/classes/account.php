<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Account class for the API engine
 */

namespace V1;

class Account
{
	/**
	 * @var integer The current GMT Unix timestamp
	 */
	protected static $gm_time = 0;
	
	/**
	 * Set up the class once it's loaded.
	 */
	public static function _init()
	{
		static::$gm_time = \Date::forge()->get_timestamp();
	}
	
	/**
	 * Authenticate to the desired account.
	 * 
	 * @return boolean True if the authentication was successful, false otherwise.
	 */
	public static function authenticate()
	{
		$auth = \Input::headers('X-Authorization');
		
		// If we're trying to run a JS call...
		if (empty($auth) && \V1\APIRequest::get('consumer_key', null) !== null) {
			
			// We're making a public call through JS, so we'll mark it as a reduced functionality call.
			\Session::set('public', true);
			
			// This session variable aids in logging and API functionality later.
			\Session::set('consumer_key', \V1\APIRequest::get('consumer_key'));
			
			$account_data = \V1\Model\Account::get_account();
			
			// If the account is invalid, fail.
			if (empty($account_data)) {
				return false;
			}
			
			/*
			 * If the account holder wishes to allow for JS based calls, we'll allow safe calls to run
			 * with their API key by turning on public mode.
			 */
			if ($account_data['js_calls_allowed'] === 0) {
				return false;
			}
			
			/**
			 * @TODO JS calls go through the client's IP, so we can't use a whitelist.
			 * In the future, perhaps a blacklist deadicated to client IPs is in order?
			 * If the account holder uses a whitelist, then they've just disabled their
			 * blacklist of the client IPs. It really should be separated, but for now
			 * it's unimplemented. 
			 */
			// IP ACL
			if (
				$account_data['acl_type'] === 0 &&
				static::ip_acl_check() === false
			) {
				return false;
			}
			
			// We're clear for lift off.
			return true;
			
		} elseif (!empty($auth)) {
			
			// Give the call full account access if we succeed in validating the request.
			\Session::set('public', false);
			
			// Is it an OAuth authorization header?
			if (\Str::sub($auth, 0, 5) !== 'OAuth') {
				return false;
			}
			
			// Parse the OAuth header into an array
			parse_str(\Str::sub($auth, 6, strlen($auth)), $tokens);
			
			$required_keys = array(
			
				'oauth_signature',
				'oauth_nonce',
				'oauth_timestamp',
				'oauth_consumer_key',
			
			);
			
			// This session variable aids in logging and API functionality later.
			if (empty($tokens['oauth_consumer_key'])) {
				return false;
			}
			
			\Session::set('consumer_key', $tokens['oauth_consumer_key']);
			
			// IP ACL
			if (static::ip_acl_check() === false) {
				return false;
			}
			
			// Do we have all the correct keys?
			if (count(array_intersect_key(array_flip($required_keys), $tokens)) !== count($required_keys)) {
				return false;
			}
				
			// Verify the data integrity of the header's components, including if the timestamp is new enough.
			if (
				!(
					isset($tokens['oauth_consumer_key'], $tokens['oauth_signature'], $tokens['oauth_nonce']) &&
					static::valid_timestamp($tokens['oauth_timestamp']) === true
				)
			) {
				return false;
			}
			
			// Do we have a valid nonce?
			if (static::valid_nonce($tokens) === false) {
				return false;
			}
			
			// Verify that the signature matches the content.
			if (static::valid_signature($tokens) === false) {
				return false;
			}
			
			// If we haven't failed yet, then it's valid.
			return true;
			
		}
		
		return false;
	}
	
	/**
	 * Downgrade an account for lack of payment if we need to
	 * 
	 * @return boolean True if the account was downgraded, false if not
	 */
	public static function downgrade()
	{
		$account_data = \V1\Model\Account::get_account();
		
		// Do we need to downgrade them?
		if (
			isset($account_data) &&
			$account_data['free_account_on'] !== 0 &&
			\Date::forge($account_data['free_account_on'])->format('%Y/%m/%d') !== \Date::forge()->format('%Y/%m/%d')
		) {
			return \V1\Model\Account::be_free_my_brother();
		}
		
		return false;
	}
	
	
	
	
	/**
	 * PROTECTED PARTS (Why do I find that funny?)
	 */
	
	/**
	 * Check if an ip is on a black or white list and allow or block the request
	 * 
	 * @return boolean True if the IP is allowed, false if it isn't.
	 */
	protected static function ip_acl_check()
	{
		$account_data = \V1\Model\Account::get_account();
		
		// No data
		if (empty($account_data)) {
			return false;
		}
		
		// IPs listed on the account for the white or black list
		if (empty($account_data['listed_ips']) || !is_array($ip_list = json_decode($account_data['listed_ips'], true))) {
			$ip_list = array();
		}
		
		// Track the server IPs contacting the account, but don't track client IPs from JS calls.
		if (\Session::get('public', true) === false) {
			static::set_used_ip();
		}
		
		$internal_whitelist = \Config::get('engine.whitelisted_ips', array());
		
		// White list
		if ($account_data['acl_type'] === 1) {
			
			// Add the IP lists together
			$ip_list += $internal_whitelist;
			
			// No data = no one can use the account since it's a white list
			if (empty($ip_list) || !is_array($ip_list)) {
				return false;
			}
			
			// The connected IP is white listed, so we allow access.
			if (in_array(\Input::real_ip(), $ip_list)) {
				return true;
			}
			
			// No access
			return false;
			
		// Black list
		} else {
			
			// Remove the Bit API Hub whitelisted IPs from the blacklist.
			$ip_list = array_diff($ip_list, $internal_whitelist);
			
			// Something messed up, so we bail on the call to ensure system integrity.
			if (!is_array($ip_list)) {
				return false;
			}
			
			// The connected IP is black listed, so we don't allow access.
			if (isset($ip_list) && in_array(\Input::real_ip(), $ip_list)) {
				return false;
			}
			
			return true;
			
		}
	}
	
	/**
	 * Add the connected IP to the list of IPs used to access the account
	 * 
	 * @return boolean True unless an error occurred
	 */
	protected static function set_used_ip()
	{
		$account_data = \V1\Model\Account::get_account();
		
		// No data
		if (empty($account_data)) {
			return false;
		}
		
		// If we can't decode the list, then we start fresh. Perhaps we overfilled the list of IPs somehow?
		if (empty($account_data['ips_used']) || !is_array($ips_used = json_decode($account_data['ips_used'], true))) {
			$ips_used = array();
		}
		
		// Add the IP if it doesn't already exist.
		if (!in_array(\Input::real_ip(), $ips_used)) {
			
			$ips_used[] = \Input::real_ip();
			
			\V1\Model\Account::set_used_ips($ips_used);
			
		}
		
		return true;
	}
	
	/**
	 * Check if a timestamp falls within the valid time window.
	 * 
	 * @param integer $timestamp The timestamp to validate
	 * @return boolean True if it's valid, false if it isn't
	 */
	public static function valid_timestamp($timestamp)
	{
		$timestamp_cutoff = \Config::get('engine.timestamp_cutoff', 15);
		
		// Is the timestamp from 60 seconds ago up to the current time?
		if ((int)$timestamp >= (static::$gm_time - (int)$timestamp_cutoff) && (int)$timestamp <= static::$gm_time) {
			return true;
		}
		
		// Timestamp out of scope
		return false;
	}
	
	/**
	 * Check for a valid nonce value
	 * 
	 * @param array $tokens The array of tokens from the OAuth header
	 * @return boolean True if the nonce is valid, or false if it isn't.
	 */
	public static function valid_nonce($tokens)
	{
		// Check if we have any previous nonces.
		try {
			$nonces = \Cache::get('account.'.$tokens['oauth_consumer_key'].'.nonces');
		} catch (\CacheNotFoundException $e) {
			$nonces = array();
		}
		
		// The array of nonces to cache
		$new_nonces = array();
		
		// If we have past nonces, check to make sure we aren't processing a replay attack.
		if (!empty($nonces)) {
			
			// Newest timestamp first
			krsort($nonces);
			
			foreach ($nonces as $timestamp => $nonce_array) {
				
				if (static::valid_timestamp($timestamp)) {
					
					// Repeated nonce = possible replay attack.
					if (in_array($tokens['oauth_nonce'], $nonce_array)) {
						return false;
					}

					// Keep the past nonces for this valid timestamp.
					$new_nonces[$timestamp] = $nonce_array;
					
				} else {
					
					// No more valid timestamps due to krsort, so we break the loop.
					break;
					
				}
				
			}
			
		}
		
		// There may be more than one nonce in a second, so we store the nonces appropriately.
		if (isset($nonces[(int)$tokens['oauth_timestamp']])) {
			$new_nonces[(int)$tokens['oauth_timestamp']] += array($tokens['oauth_nonce']);
		} else {
			$new_nonces[(int)$tokens['oauth_timestamp']] = array($tokens['oauth_nonce']);
		}
		
		\Cache::set('account.'.$tokens['oauth_consumer_key'].'.nonces', $new_nonces, null);
		return true;
	}
	
	/**
	 * Validate the signature for the call
	 * 
	 * @param array $tokens The OAuth tokens from the header
	 * @return boolean True if valid, false if invalid
	 */
	protected static function valid_signature($tokens)
	{
		$mt = microtime(true);
		// Decode the signature, or fail
		if (($decoded_sig = urldecode(base64_decode($tokens['oauth_signature']))) === false) {
			return false;
		}
		
		// Grab the account data so we have a copy of the customer's secret key.
		$account_data = \V1\Model\Account::get_account($tokens['oauth_consumer_key']);
		
		// If the account is invalid, fail.
		if (empty($account_data)) {
			return false;
		}
		
		$secret = \Crypt::decode($account_data['consumer_secret']);
		
		// Reconstruct the data to build the signature.
		$oauth = array(
		
			'oauth_nonce'			=> $tokens['oauth_nonce'],
			'oauth_timestamp'		=> $tokens['oauth_timestamp'],
			'oauth_consumer_key'	=> $tokens['oauth_consumer_key'],
			'oauth_consumer_secret'	=> $secret,
			'body'					=> urlencode(urlencode(base64_encode(json_encode(\V1\APIRequest::post_data())))),
				
		);
		
		ksort($oauth);
		
		$oauth_encoded = array();
		
		foreach ($oauth as $key => $value) {
			$oauth_encoded[] = $key.'='.$value;
		}
		
		// Now we have the string to make the hash
		$signed_string = urlencode(implode('&', $oauth_encoded));
		
		// Final product
		$hash = hash_hmac('sha256', $signed_string, $secret);
		
		// If they match, it's valid.
		return $hash === $decoded_sig;
	}
}

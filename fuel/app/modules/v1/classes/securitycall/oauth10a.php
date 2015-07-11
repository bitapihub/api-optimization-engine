<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * OAuth 1.0a call object
 * 
 * @link http://oauth.net/core/1.0a/
 */

namespace V1\SecurityCall;

class OAuth10a extends \V1\SecurityCall\Template\SecurityCallAbstract
{
	/**
	 * Signify if the object is in error status
	 * @var bool
	 */
	public $error = false;
	
	/**
	 * The current instance
	 * @var object
	 */
	private static $instance = null;
	
	/**
	 * The ID for cache storage
	 * @var string
	 */
	private $cache_id = null;

	/**
	 * Forge a new object
	 * 
	 * @return object
	 */
	public static function forge()
	{
		if (empty(static::$instance)) {
			static::$instance = new static;
		}
		
		return static::$instance;
	}
	
	/**
	 * Run the request
	 * 
	 * @param \Raml\SecurityScheme $securityscheme_obj The security scheme to process the call data for
	 * @param \V1\APICall $apicall_obj					The APICall object
	 * 
	 * @return mixed The object we just completed or an array describing the next step in the security process
	 */
	public function run(\Raml\SecurityScheme $securityscheme_obj, \V1\APICall $apicall_obj)
	{
		$settings			= $securityscheme_obj->getSettings()->asArray();
		$credentials		= $apicall_obj->get_credentials();
		
		$settings['authorization'] = empty($settings['authorization']) ? 'header' : \Str::lower($settings['authorization']);
		
		// Verify that we have the required credentials for the request.
		if (
			empty($credentials['OAUTH_CONSUMER_KEY']) ||
			empty($credentials['OAUTH_CONSUMER_SECRET']) ||
			empty($credentials['OAUTH_USER_ID'])
		) {
			
			$this->error = true;
			return $this;
			
		}
		
		// Store the proper credentials in the DB.
		$this->store_credentials($credentials);
		
		// Pull data from the cache for the current request, allowing for multiple authentications for the customer.
		$this->cache_id = hash(
			'sha256',
			$credentials['OAUTH_CONSUMER_KEY'].
			$credentials['OAUTH_CONSUMER_SECRET'].
			$credentials['OAUTH_USER_ID']
		);
		
		$credentials = array_replace($this->get_cache(), $credentials);
		
		// Where should we set the authorization data?
		switch ($settings['authorizeLocation']) {
			
			case 'header':
				$authorize_location = OAUTH_AUTH_TYPE_AUTHORIZATION;
				break;
			
			case 'query':
				$authorize_location = OAUTH_AUTH_TYPE_URI;
				break;
			
			case 'body':
				$authorize_location = OAUTH_AUTH_TYPE_FORM;
				break;
			
			case 'none':
				$authorize_location = OAUTH_AUTH_TYPE_NONE;
				break;
			
		}
		
		try {
			
			// Create the PECL installed OAuth object.
			$oauth = new \OAuth(
				$credentials['OAUTH_CONSUMER_KEY'],
				$credentials['OAUTH_CONSUMER_SECRET'],
				$settings['signatureMethod'], // RSA-SHA1, HMAC-SHA1, HMAC-SHA256, PLAINTEXT
				$authorize_location
			);
			
			if (\Fuel::$env !== 'production') {
				$oauth->enableDebug();
			}
			
			if(
				empty($credentials['OAUTH_ACCESS_TOKEN']) ||
				empty($credentials['OAUTH_ACCESS_TOKEN_SECRET'])
			) {
				
				// Get our access token and secret.
				if (($credentials = $this->get_access_tokens($oauth, $settings, $credentials)) === false) {
					
					$this->error = true;
					return $this;
					
				}
				
				// Authentication of my second leg (Yup. It's hairy, so it must be mine.)
				if (!empty($credentials['errors'])) {
					return $credentials;
				}
				
			}
			
			$oauth->setToken(
				$credentials['OAUTH_ACCESS_TOKEN'],
				$credentials['OAUTH_ACCESS_TOKEN_SECRET']
			);
			
			// Collect parameters to build our signature
			$params = null;
			if ($apicall_obj->get_body_type() === 'application/x-www-form-urlencoded') {
				
				// If we need to handle string bodies later, we will.
				if (is_array($apicall_obj->get_method_params())) {
					$params = http_build_query($apicall_obj->get_method_params(), null, '&', PHP_QUERY_RFC3986).'&';
				}
				
			}
			
			$params .= http_build_query($apicall_obj->get_query_params(), null, '&').'&'.
			$params .= http_build_query($apicall_obj->get_headers(), null, '&');
			$header = $oauth->getRequestHeader($apicall_obj->get_method(), $apicall_obj->get_url(), $params);
			
			$apicall_obj->set_header('Authorization', $header);
			return true;
			
		} catch(\OAuthException $e) {
			
			// Something went wrong, so destroy the cache so it can get fixed.
			$this->delete_cache();
			
			// Let the script automatically continue searching for security methods.
			$this->error = true;
			return $this;
			
		}
		
	}
	
	/**
	 * Grab the API access token and key, and return the array of credentials
	 * 
	 * @param \OAuth $oauth			The OAuth object we're using for authentication
	 * @param array $settings		The array of RAML settings
	 * @param array $credentials	The array of credentials for our call
	 * 
	 * @return array The array of credentials, or an error array when the customer must manually
	 * 					validate for the second leg, or false on fail
	 */
	private function get_access_tokens(\OAuth $oauth, array $settings, array $credentials)
	{
		$callback_url = \Config::get('engine.oauth_10a_callback_url', null);
		if (!empty($settings['callback'])) {
			$callback_url = $settings['callback'];
		}
		
		// Grab the request token if we didn't just authorize the request.
		if (
			empty($credentials['OAUTH_REQUEST_TOKEN']) &&
			empty($credentials['OAUTH_REQUEST_TOKEN_SECRET'])
		) {
			
			try {
				
				$request_token_info = $oauth->getRequestToken(
					$settings['requestTokenUri'],
					$callback_url,
					$settings['requestTokenMethod']
				);
				
			} catch (\OAuthException $e) {
				
				// Something went wrong, so destroy the cache, and return false so it can get fixed.
				$this->delete_cache();
				return false;
				
			}
			
			$credentials['OAUTH_REQUEST_TOKEN']			= $request_token_info['oauth_token'];
			$credentials['OAUTH_REQUEST_TOKEN_SECRET']	= $request_token_info['oauth_token_secret'];
			
		}
		
		if (empty($credentials['OAUTH_VERIFIER'])) {
			$credentials['OAUTH_VERIFIER'] = null;
		}
		
		// Three legged auth requires manual validation.
		if ($settings['legs'] === 3) {
			
			if (
				empty($credentials['OAUTH_REQUEST_TOKEN']) ||
				empty($credentials['OAUTH_REQUEST_TOKEN_SECRET']) ||
				empty($credentials['OAUTH_VERIFIER'])
			) {
		
				if (strpos($settings['authorizationUri'], '?') === false) {
					$settings['authorizationUri'] .= '?';
				} else {
					$settings['authorizationUri'] .= '&';
				}
		
				// Save the request tokens.
				$this->set_cache($credentials);
				
				/*
				 * Only non-static calls may show the authorization URL so as to not mislead people into
				 * authorizing their accounts for everyone connected to the API hub.
				 */
				if (\V1\APIRequest::is_static() === false) {
					
					// Tell them were to go.
					return \Utility::format_error(
						401,
						\V1\Err::OAUTH1_AUTHORIZE,
						\Lang::get(
							'v1::errors.oauth1_authorize',
							array(
								'url' => $settings['authorizationUri'].'oauth_token='.$request_token_info['oauth_token'],
							)
						)
					);
					
				} else {
					return false;
				}
		
			}
				
		}
		
		$oauth->setToken(
			$credentials['OAUTH_REQUEST_TOKEN'],
			$credentials['OAUTH_REQUEST_TOKEN_SECRET']
		);
		
		try {

			$access_token_info = $oauth->getAccessToken(
				$settings['tokenCredentialsUri'],
				null,
				$credentials['OAUTH_VERIFIER'],
				$settings['tokenCredentialsMethod']
			);
			
		} catch (\OAuthException $e) {
		
			// Something went wrong, so destroy the cache, and return false so it can get fixed.
			$this->delete_cache();
			return false;
			
		}
		
		// Clean up our data, and set the access key and secret in the DB.
		unset($credentials['OAUTH_REQUEST_TOKEN']);
		unset($credentials['OAUTH_REQUEST_TOKEN_SECRET']);
		unset($credentials['OAUTH_VERIFIER']);
		
		$credentials['OAUTH_ACCESS_TOKEN']			= $access_token_info['oauth_token'];
		$credentials['OAUTH_ACCESS_TOKEN_SECRET']	= $access_token_info['oauth_token_secret'];
		
		// Keep the credentials cached
		$this->set_cache($credentials);
		
		return $credentials;
	}
	
	/**
	 * Get a cahed value for the current client
	 * 
	 * @param string $entry The name of the cached value
	 * @param mixed $return	The value to return on false
	 * 
	 * @return mixed The value for the entry or $return on fail
	 */
	private function get_cache($entry = null, $return = array())
	{
		try {
			
			$cache = \Cache::get('oauth_10a_cache.'.$this->cache_id);
			
			if (empty($entry)) {
				return $cache;
			}
			
			if (!empty($cache[$entry])) {
				return $cache[$entry];
			}
			
		} catch (\CacheNotFoundException $e) {}
		
		return $return;
	}
	
	/**
	 * Set a value in the cache
	 * 
	 * @param mixed $entry	The name of the entry to set or the array to store in cache
	 * @param mixed $value	The value to set for the entry
	 */
	private function set_cache($entry, $value = null)
	{
		$cache = array();
		try {
			$cache = \Cache::get('oauth_10a_cache.'.$this->cache_id);
		} catch (\CacheNotFoundException $e) {}
		
		if (is_array($entry)) {
			$cache = $entry;
		} else {
			$cache[$entry] = $value;
		}
		
		\Cache::set('oauth_10a_cache.'.$this->cache_id, $cache, null);
	}
	
	/**
	 * Delete the entire cache for the client we're authenticating
	 */
	private function delete_cache()
	{
		\Cache::delete('oauth_10a_cache.'.$this->cache_id);
	}
	
	/**
	 * Select the proper credentials to store in the DB.
	 * 
	 * @param array $credentials The array of credentials to store
	 */
	private function store_credentials(array $credentials)
	{
		// We'll pitch any OAuth 1.0a credentials so that we can choose what to store later.
		$set_credentials = $credentials;
		foreach ($set_credentials as $variable => $value) {
				
			if (strpos($variable, 'OAUTH_') !== false) {
				unset($set_credentials[$variable]);
			}
				
		}
		
		// We'll save the consumer key and secret, plus the callback URL if we have one.
		$set_credentials = array(
				
			'OAUTH_CONSUMER_KEY'	=> $credentials['OAUTH_CONSUMER_KEY'],
			'OAUTH_CONSUMER_SECRET'	=> $credentials['OAUTH_CONSUMER_SECRET'],
			'OAUTH_USER_ID'			=> $credentials['OAUTH_USER_ID'],
				
		);
		
		\V1\Keyring::set_credentials($set_credentials);
	}
}

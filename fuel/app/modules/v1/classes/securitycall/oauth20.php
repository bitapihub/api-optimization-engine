<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * OAuth 2.0 call object
 * 
 * @link http://oauth.net/2/
 */

namespace V1\SecurityCall;

class OAuth20 extends \V1\SecurityCall\Template\SecurityCallAbstract
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
		$settings		= $securityscheme_obj->getSettings()->asArray();
		$credentials	= $apicall_obj->get_credentials();
		
		// Verify that we have the required credentials for the request.
		if (
			empty($credentials['OAUTH2_CLIENT_ID']) ||
			empty($credentials['OAUTH2_CLIENT_SECRET']) ||
			empty($credentials['OAUTH2_USER_ID'])
		) {
			\Debug::dump($credentials);
			$this->error = true;
			return $this;
			
		}
		
		// Cache ID for the fkooman client's storage
		$cache_id = hash(
			'sha256',
			$credentials['OAUTH2_CLIENT_ID'].
			$credentials['OAUTH2_CLIENT_SECRET'].
			$credentials['OAUTH2_USER_ID']
		);
		
		// Find the default scope so we don't get errors. First, check the cache.
		if (($default_scopes = $this->get_default_scope($cache_id)) === false) {
			
			// We don't have the data cached, so build the scopes from known data.
			if (($scopes = $this->get_scopes($credentials, $settings)) === false) {
				
				$this->error = true;
				return $this;
				
			}
			
			$default_scopes = implode(' ', $scopes);
			
		}
		
		// Find the redirect URI.
		$redirect_uri = \Config::get('engine.oauth_20_redirect_uri', null);
		if (!empty($credentials['OAUTH2_REDIRECT_URI'])) {
			$redirect_uri = $credentials['OAUTH2_REDIRECT_URI'];
		}
		
		// Configure the OAuth 2.0 module.
		$configure = array(
			'redirect_uri'					=> $redirect_uri,
			'authorize_endpoint'			=> $settings['authorizationUri'],
			'client_id'						=> $credentials['OAUTH2_CLIENT_ID'],
			'client_secret'					=> $credentials['OAUTH2_CLIENT_SECRET'],
			'token_endpoint'				=> $settings['accessTokenUri'],
			'credentials_in_request_body'	=> (bool) $settings['includeCredsInRequest'],
			'default_token_type'			=> $settings['tokenType'],
			'default_server_scope'			=> $default_scopes,
			'allow_null_expires_in'			=> $settings['expiresType'] === 'none' ? true : false,
			'allow_string_expires_in'		=> $settings['expiresType'] === 'string' ? true : false,
			'use_comma_separated_scope'		=> $settings['scopeType'] === 'comma' ? true : false,
			'use_array_scope'				=> $settings['scopeType'] === 'array' ? true : false,
			'use_redirect_uri_on_refresh_token_request'	=> true,
		);
		
		if (\Fuel::$env !== 'production') {
			$configure['enable_debug'] = true;
		}
		
		if (
			!empty($credentials['OAUTH2_STATE']) &&
			!empty($credentials['OAUTH2_CODE'])
		) {
			
			// Authorization complete.
			if (($access_token_obj = $this->get_callback_access_token($cache_id, $configure, $credentials)) === false) {
				
				$this->error = true;
				return $this;
				
			}

			
		} else {
			
			// Locate the access token
			if (($access_token_obj = $this->run_request_run($cache_id, $configure, $credentials, $settings)) === false) {
				
				$this->error = true;
				return $this;
				
			}
			
			// Redirect URL message
			if (is_array($access_token_obj)) {
				
				$this->store_credentials($credentials);
				return $access_token_obj;
				
			}
			
		}
		
		// We have an access token in the form of an AccessToken object, so let's form the header.
		if ($access_token_obj instanceof \fkooman\OAuth\Client\AccessToken) {
			
			$header = ucfirst($access_token_obj->getTokenType()).' '.$access_token_obj->getAccessToken();
			$apicall_obj->set_header('Authorization', $header);
			
			$this->store_credentials($credentials);
			
			return true;
			
		}
	}
	
	/**
	 * Store the array of credentials in the DB
	 * 
	 * @param array $credentials The array of credentials to store
	 */
	private function store_credentials(array $credentials)
	{
		// We'll pitch any OAuth 2.0 credentials so that we can choose what to store later.
		$set_credentials = $credentials;
		foreach ($set_credentials as $variable => $value) {
		
			if (strpos($variable, 'OAUTH2_') !== false) {
				unset($set_credentials[$variable]);
			}
		
		}
		
		\V1\Keyring::set_credentials($set_credentials);
	}
	
	/**
	 * Run a call to grab the AccessToken object or grab the object from cache
	 * 
	 * @param string $cache_id		The cache ID for the cache storage object
	 * @param array $client_config	The array of configuration data for the fkooman module
	 * @param array $credentials	The array of credentials from the DB
	 * @param array $settings		The array of RAML settings data
	 * 
	 * @return mixed The AccessToken object if we have one, the array of error data to show the
	 * 					authorization URL, or false on fail
	 */
	private function run_request_run($cache_id, array $client_config, array $credentials, array $settings)
	{
		// Try to get the access token. The fkooman module will auto-refresh the token as needed.
			
		$client_config_id = (string) uniqid();
		$this->set_client_config_id($cache_id, $client_config_id);
		
		/**
		 * SCOPES
		*/
		
		if (($scopes = $this->get_scopes($credentials, $settings)) === false) {
			return false;
		}
			
		$this->set_default_scope($cache_id, $scopes);
		$client_config['default_server_scope'] = implode(' ', $scopes);
		
		/**
		 * @TODO Make the script able to processes all types of OAuth 2.0 requests. (authorizationGrants)
		 *
		 * @link http://raml.org/spec.html#oauth-2-0
		*/
			
		try {
		
			$api = new \fkooman\OAuth\Client\Api(
				$client_config_id,
				new \fkooman\OAuth\Client\ClientConfig($client_config),
				new \V1\SecurityCall\OAuth20\CacheStorage($cache_id),
				new \Guzzle\Http\Client()
			);
			
			$context = new \fkooman\OAuth\Client\Context(\Session::get('consumer_key'), $scopes);
			$access_token_obj = $api->getAccessToken($context);
			
			// No access token, so must authorize the call.
			if ($access_token_obj === false) {
		
				/*
				 * Only non-static calls may show the authorization URL so as to not mislead people into
				 * authorizing their accounts for everyone connected to the API hub. Admins may force it to show.
				 */
				if (\V1\APIRequest::is_static() === false || \V1\APIRequest::get('show-auth-callback', false) === true) {
						
					// Tell them were to go.
					return \Utility::format_error(
						401,
						\V1\Err::OAUTH2_AUTHORIZE,
						\Lang::get('v1::errors.oauth2_authorize',array('url' => $api->getAuthorizeUri($context)))
					);
						
				} else {
					return false;
				}
		
			}
			
			return $access_token_obj;
		
		} catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Get the AccessToken object after processing a callback
	 * 
	 * @param string $cache_id		The cache ID for the cache storage object
	 * @param array $client_config	The array of configuration data for the fkooman module
	 * @param array $credentials	The array of credentials from the DB
	 * 
	 * @return mixed The AccessToken object or false on fail
	 */
	private function get_callback_access_token($cache_id, array $client_config, array $credentials)
	{
		// We need the client config ID. If the cache timed out, we'll probably get this error.
		if (($client_config_id = $this->get_client_config_id($cache_id)) === false) {
			return false;
		}
			
		/**
		 * The client should only send non-error requests to the API Engine as there isn't any
		 * reason to send error responses.
		 */
			
		$callback_data = array(
		
			'state'	=> $credentials['OAUTH2_STATE'],
			'code'	=> $credentials['OAUTH2_CODE'],
		
		);
			
		try {
		
			$callback = new \fkooman\OAuth\Client\Callback(
				$client_config_id,
				new \fkooman\OAuth\Client\ClientConfig($client_config),
				new \V1\SecurityCall\OAuth20\CacheStorage($cache_id),
				new \Guzzle\Http\Client()
			);
			
			return $callback->handleCallback($callback_data);
		
		} catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Get the array of scopes for the request
	 * 
	 * @param array $credentials	The array of credentials we're using
	 * @param array $settings		The array of RAML settings data
	 * 
	 * @return array The array of scopes or an empty array
	 */
	private function get_scopes(array &$credentials, array $settings)
	{
		$scopes = array();
		
		// User specified scopes
		if (!empty($credentials['OAUTH2_SCOPES'])) {
		
			// It should never be an array, but we'll check it to prevent errors.
			if (!is_array($credentials['OAUTH2_SCOPES'])) {
		
				$delim = false;
		
				// If they've specified multiple scopes, then we'll use them all.
				if (strpos($credentials['OAUTH2_SCOPES'], ',') !== false) {
					$delim = ',';
				} elseif (strpos($credentials['OAUTH2_SCOPES'], ' ') !== false) {
					$delim = ' ';
				} else {
						
					// Single scope
					$scopes = array($credentials['OAUTH2_SCOPES']);
					$credentials['OAUTH2_SCOPES'] = $scopes;
						
				}
		
				// Process the scopes if they're meant to be an array
				if ($delim !== false) {
						
					$scope_entries = explode($delim, $credentials['OAUTH2_SCOPES']);
					foreach ($scope_entries as $entry) {
						$scopes[] = trim($entry);
					}
		
				}
					
			}
		
		}
			
		// System specified scopes
		if (empty($scopes) && !empty($settings['scopes'])) {
			$scopes = $settings['scopes'];
		}
		
		// No scopes.
		if (empty($scopes)) {
			return array();
		}
		
		return $scopes;
	}
	
	/**
	 * Set the client config ID for the fkooman script so that we can persist it accross multiple calls.
	 * 
	 * @param string $cache_id			The cache ID we're using to differenciate between different client accounts
	 * @param string $client_config_id	The client config ID for the fkooman script 
	 */
	private function set_client_config_id($cache_id, $client_config_id)
	{
		\Cache::set('fkooman_client_config_id.'.$cache_id, $client_config_id);
	}
	
	/**
	 * Get the client config ID for the fkooman script.
	 * 
	 * @param string $cache_id The cache ID we're using to differenciate between different client accounts
	 * @return mixed The client config ID or false on fail
	 */
	private function get_client_config_id($cache_id)
	{
		try {
			return \Cache::get('fkooman_client_config_id.'.$cache_id);
		} catch (\CacheNotFoundException $e) {
			return false;
		}
	}
	
	/**
	 * Set the default scope.
	 * 
	 * @param string $cache_id	The cache ID we're using to differenciate between different client accounts
	 * @param array $scopes		The array of scopes to use as the default scope
	 */
	private function set_default_scope($cache_id, array $scopes)
	{
		\Cache::set('fkooman_default_scope.'.$cache_id, implode(' ', $scopes));
	}
	
	/**
	 * Get the default scope.
	 * 
	 * @param string $cache_id	The cache ID we're using to differenciate between different client accounts
	 * @return mixed The default scope or false on fail
	 */
	private function get_default_scope($cache_id)
	{
		try {
			return \Cache::get('fkooman_default_scope.'.$cache_id);
		} catch (\CacheNotFoundException $e) {
			return false;
		}
	}
}

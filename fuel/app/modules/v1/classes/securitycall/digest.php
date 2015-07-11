<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Digest Access Authentication call object
 * 
 * @link https://en.wikipedia.org/wiki/Digest_access_authentication
 */

namespace V1\SecurityCall;

class Digest extends \V1\SecurityCall\Template\SecurityCallAbstract
{
	/**
	 * Signify if the object is in error status
	 * @var bool
	 */
	public $error = false;
	
	/**
	 * The APICall object
	 * @var \V1\APICall
	 */
	private $apicall = null;
	
	/**
	 * The array of credentials we'll use for the call
	 * @var array
	 */
	private $credentials = array();
	
	/**
	 * The number of times the current nonce was used
	 * @var int
	 */
	private $nonce_count = 0;
	
	/**
	 * The current client nonce
	 * @var string
	 */
	private $cnonce = null;
	
	/**
	 * The current remote server's nonce
	 * @var string
	 */
	private $nonce = null;
	
	/**
	 * The opaque value to send to the server
	 * @var string
	 */
	private $opaque = null;
	
	/**
	 * The value for the qop as specified in the RAML config (Or empty if it isn't used)
	 * @var unknown
	 */
	private $qop = null;
	
	/**
	 * A dragon lives in my own private realm... On the server of course; on the server!
	 * @var string
	 */
	private $realm = null;
	
	/**
	 * The username we'll use to authenticate
	 * @var string
	 */
	private $username = null;
	
	/**
	 * An array of data parsed from WWW-Authentication IF we accessed the remote server, false if we never
	 * accessed it, or true if we've accessed it and it didn't have what we needed. 
	 * @var mixed
	 */
	private $www_data = false;
	
	/**
	 * The first part of the hashed authentication
	 * @var string
	 */
	private $ha1 = null;
	
	/**
	 * The second part of the hashed authentication
	 * @var string
	 */
	private $ha2 = null;
	
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
				// If the customer enters an authorization header, then we bail out of the security call.
		if (
			\V1\APIRequest::is_static() === false &&
			!empty($custom_headers = \V1\APIRequest::get('configure.headers', null)) &&
			!empty($custom_headers['Authorization'])
		) {
			return true;
		}
		
		$this->apicall = $apicall_obj;
		
		/**
		 * GATHER THE NEEDED DATA
		 */
		$credentials	= $apicall_obj->get_credentials();
		$settings		= $securityscheme_obj->getSettings();
		$api_def		= $apicall_obj->get_api_def();
		$api_url		= $apicall_obj->get_url();
		$method			= $apicall_obj->get_method();
		
		$this->credentials = $credentials;
		
		// Digest URI
		$parsed_url = parse_url($api_url);
		$digest_uri = empty($parsed_url['path']) ? '/' : $parsed_url['path'];
		
		if (!empty($parsed_url['query'])) {
			$digest_uri .= '?'.$parsed_url['query'];
		}
		
		$algorithm = empty($settings['algorithm']) ? 'md5' : \Str::lower($settings['algorithm']);
		
		// Make sure that we have the required data.
		if (
			empty($credentials['DIGEST_USERNAME']) ||
			empty($credentials['DIGEST_PASSWORD']) ||
			empty($settings['realm'])
		) {
			
			$this->error = true;
			return;
			
		}
		
		$this->realm	= $settings['realm'];
		$this->username	= $credentials['DIGEST_USERNAME'];
		
		// Find our nonce.
		if (empty($credentials['DIGEST_NONCE'])) {
			
			// Beg for nonces
			$this->parse_www_auth_remote($api_url);
			if (
				is_array($this->www_data) &&
				!empty($this->www_data['nonce'])
			) {
				$this->nonce = $this->www_data['nonce'];
			} else {
		
				$this->error = true;
				return;
		
			}
		
		} else {
			$this->nonce = $credentials['DIGEST_NONCE'];
		}
		
		// We save this value in the DB.
		$credentials['DIGEST_NONCE'] = $this->nonce;
		
		// Figure out if we've used the current nonce before.
		if (!empty($settings['qop'])) {
			
			// We may have "auth" or "auth-int" or "auth,auth-int" or "auth-int,auth"
			if (substr_count(\Str::lower($settings['qop']), 'auth-int') > 0) {
				$this->qop = 'auth-int';
			} else {
				$this->qop = 'auth';
			}
				
			/**
			 * We have a qop, so we need to figure out how many times we've sent a request with
			 * the current nonce. (Including the current request)
			 *
			 * @link http://www.ietf.org/rfc/rfc2617.txt
			 * Look up "nonce-count"
			 */
			
			if (empty($credentials['DIGEST_NONCE_COUNT'])) {
				$credentials['DIGEST_NONCE_COUNT'] = 0;
			}
			
			$this->nonce_count = ++$credentials['DIGEST_NONCE_COUNT'];
			
		}
		
		// Do we need to send the "opaque" param?
		if (!empty($settings['opaque'])) {
			
			// It stays the same for the requester forevermore.
			if ($settings['opaque'] === 'same') {
				
				// We have the value on file. (Dynamic calls only)
				if (!empty($credentials['DIGEST_OPAQUE'])) {
					$this->opaque = $credentials['DIGEST_OPAQUE'];
				}
				
			}
			
			// If it isn't set to "same" or "changes," then we have a static value.
			if ($settings['opaque'] !== 'changes') {
				$this->opaque = $settings['opaque'];
			}
			
			// We couldn't find the value, so we pull it from the header data of a new request.
			if (empty($this->opaque)) {
				
				// If we never contacted the remote server, do that now.
				if ($this->www_data === false) {
					$this->parse_www_auth_remote($api_url);
				}
				
				if (
					is_array($this->www_data) &&
					!empty($this->www_data['opaque'])
				) {
					
					$this->opaque = $this->www_data['opaque'];
					
					// We'll save it since it'll always be the same.
					if ($settings['opaque'] === 'same') {
						$credentials['DIGEST_OPAQUE'] = $this->opaque;
					}
					
				} else {
					
					// We've called the remote server and it didn't have the data.
					$this->error = true;
					return;
					
				}
				
			}
			
		}
		
		/*
		 * Increment our nonce counter for the current request with the nonce. (Pardon me while I go get a
		 * bowl of de Chex.)
		 */
		$this->nonce_count = dechex($this->nonce_count);
		
		/**
		 * Format the nonce count as specified in section 3.2.2 of the RFC2617.
		 * 
		 * @link http://www.ietf.org/rfc/rfc2617.txt
		 */
		if (($padding = (8 - strlen($this->nonce_count))) > 0) {
			$this->nonce_count = str_repeat(0, $padding).$this->nonce_count;
		}
		
		// Reliable client nonce
		$this->cnonce = \Utility::get_nonce();
		
		/**
		 * START COMPILING THE HEADER
		 */
		
		// MD5
		$this->ha1 = md5(
			$credentials['DIGEST_USERNAME'].':'.$this->realm.':'.$credentials['DIGEST_PASSWORD']
		);
		
		// MD5-sess
		if ($algorithm === 'md5-sess') {
			$this->ha1 = md5($this->ha1.':'.$this->nonce.':'.$this->cnonce);
		}
		
		\V1\Keyring::set_credentials($credentials);
		
		// We've finished for now. We'll configure more just before we send the request.
	}
	
	/**
	 * Process the request data to include the new headers.
	 * 
	 * @param string $request The entire request to the remote server including headers and the body
	 * @return string The altered $request data
	 */
	public function before_send($request)
	{
		/**
		 * @TODO Finish processing the HA2 data, then form the header. Check if APICall set a
		 * header with the same name, and if so, overwrite it in the request string.
		 */
		
		$request_parts	= explode("\r\n\r\n", $request);
		$uri			= $this->apicall->get_uri();
		$method			= \Str::upper($this->apicall->get_method());
		
		/**
		 * HA2
		 */
		if (empty($this->qop) || \Str::lower($this->qop === 'auth')) {
			$this->ha2 = md5($method.':'.$uri);
		} else {
			
			/**
			 * If we don't have a body, then we hash null.
			 * 
			 * @link http://curl.haxx.se/mail/tracker-2013-06/0083.html
			 */
			$body = empty($request_parts[1]) ? 'd41d8cd98f00b204e9800998ecf8427e' : md5($request_parts[1]);
			$this->ha2 = md5($method.':'.$uri.':'.$body);
			
		}
		
		/**
		 * RESPONSE
		 */
		if (empty($this->qop)) {
			$response = md5($this->ha1.':'.$this->nonce.':'.$this->ha2);
		} else {
			$response = md5($this->ha1.':'.$this->nonce.':'.$this->nonce_count.':'.$this->cnonce.':'.$this->qop.':'.$this->ha2);
		}
		
		// Protect against CRLF, and add the header.
		$auth_header = str_replace("\r\n", '', 'Authorization: Digest username="'.$this->username.
		'",realm="'.$this->realm.'",nonce="'.$this->nonce.'",uri="'.$uri.'",qop='.$this->qop.
		',nc='.$this->nonce_count.',cnonce="'.$this->cnonce.'",response="'.$response.
		'",opaque="'.$this->opaque.'"');
		
		$request_parts[0] .= "\r\n".$auth_header;
		$request = implode("\r\n\r\n", $request_parts);
		
		return $request;
	}
	
	/**
	 * Touch up some settings after we've grabbed the response from the remote server.
	 * 
	 * @param array $response The array of response data from \V1\Socket
	 * @return array The $response array with any needed alterations
	 */
	public function after_response(array $response)
	{
		// If the authentication was invalid, the server will spit out a 401 response, and give us a new nonce.
		if ($response['status'] === 401 && !empty($response['headers']['WWW-Authenticate'])) {
			
			if (
				is_array($www_auth = $this->parse_www_auth($response['headers'])) &&
				!empty($www_auth['nonce'])
			) {
				
				/*
				 * Set the new nonce, and reset the number of calls that we've made with the nonce, thus
				 * defeating the whole point of a number once, as per the RFC2617. Gotta love people who
				 * don't know how to program! (Sarcasm)
				 */
				$this->credentials['DIGEST_NONCE']			= $www_auth['nonce'];
				$this->credentials['DIGEST_NONCE_COUNT']	= 0;
				\V1\Keyring::set_credentials($this->credentials);
				
			}
			
		}
		
		return $response;
	}
	
	/**
	 * Request the WWW-Authentication header from the remote server, and parse it.
	 * 
	 * @param string $url The URL to contact and beg for a nonce
	 * @return bool True on success, or false on fail
	 */
	private function parse_www_auth_remote($url)
	{
		// We tried to pull the data, so if we can't get an array later, we'll know what happened.
		$this->www_data = true;
		
		$curl = \Remote::forge($url, 'curl', 'head');
		
		try {
			$headers = $curl->execute()->headers;
		} catch (\RequestStatusException $e) {
			$headers = \Remote::get_response($curl)->headers;
		} catch (\RequestException $e) {
			return false;
		}
		
		if (is_array($parsed = $this->parse_www_auth($headers))) {
			
			$this->www_data = $parsed;
			return true;
			
		} else{
			return false;
		}
	}
	
	/**
	 * Parse the WWW-Authenticate data
	 * 
	 * @param array $headers The array of headers from the response
	 * @return mixed The array of parsed WWW-Authentication data, or boolean false on fail
	 */
	private function parse_www_auth(array $headers)
	{
		/*
		 * Looking for:
		 * WWW-Authenticate: Digest realm="testrealm@host.com",qop="auth,auth-int",nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093",opaque="5ccc069c403ebaf9f0171e9517f40e41"
		 */
		
		$return = false;
		
		// Loop the response headers
		foreach ($headers as $header_name => $header_value) {
				
			// Find the WWW-Authenticate header
			if (\Str::lower($header_name) === 'www-authenticate') {
		
				$params = explode(' ', $header_value);
				
				if (\Str::upper($params[0]) !== 'DIGEST') {
					return false;
				}
				
				unset($params[0]);
				$param_array = explode(',', implode(' ', $params));
		
				$return = array();
		
				// Loop the tokens (array('nonce=675858','poq=...'))
				foreach ($param_array as $param) {
					
					// Find that fish!
					$token_data = explode('=', $param);
						
					$token_name = $token_data[0];
					unset($token_data[0]);
						
					// Remove the quotes if we need to.
					$token_value = implode('=', $token_data);
					if (substr($token_value, 0, 1) === '"') {
						$token_value = substr($token_value, 1, strlen($token_value)-2);
					}
					
					// Set out tokens.
					$return[$token_name] = $token_value;
						
				}
		
				if (empty($return)) {
					
					// We found the header and attempted to parse it.
					return false;
					
				}
		
			}
				
		}
		
		return $return;
	}
}

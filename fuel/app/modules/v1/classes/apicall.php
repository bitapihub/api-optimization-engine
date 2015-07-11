<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * API call object - An interface between RAML data and ready to use arrays for the call
 */

namespace V1;

class APICall
{
	/**
	 * @var array $url The URL for the call 
	 */
	private $url = null;
	
	/**
	 * @var array $headers The array of request headers
	 */
	private $headers = array();
	
	/**
	 * @var array $query_params The array of query params for the request
	 */
	private $query_params = array();
	
	/**
	 * @var string $method The method we'll use for the call
	 */
	private $method = null;
	
	/**
	 * @var mixed $method_params The array of parameters to send in the body, or the string to use for the body
	 */
	private $method_params = array();
	
	/**
	 * @var string $body_type The encoding type for the body
	 */
	private $body_type = null;
	
	/**
	 * @var array $security_calls The array of security calls to make before the main call
	 */
	private $security_calls = array();
	
	/**
	 * @var object The \Raml\APIDefinition object
	 */
	private $api_def = null;
	
	/**
	 * @var string $raml_route The route as specified in the RAML schema
	 */
	private $raml_route = null;
	
	/**
	 * @var string The remote resource's URI
	 */
	private $call_uri = null;
	
	/**
	 * @var array $errors The array of error response data
	 */
	private $errors = array();
	
	/**
	 * @var array $credentials The array of credentials for the API
	 */
	private $credentials = array();
	
	/**
	 * @var bool $custom_dynamic Is this call listed in the RAML spec?
	 */
	private $custom_dynamic = false;
	
	/**
	 * Forge a new API call object
	 * 
	 * @param \Raml\ApiDefinition $api_def	The \Raml\APIDefinition object to work with
	 * @param string $method				The method for the call
	 * @param string $call_uri				The URI for the call (Without an internal prefix)
	 * @param string $raml_route			The URI as set in the RAML schema (With internal prefix)
	 * @param bool $custom_dynamic			Set to true to disable RAML validation, or false not to.
	 * 
	 * @return APICall The completed object
	 */
	public static function forge(
		\Raml\ApiDefinition $api_def,
		$method,
		$call_uri,
		$raml_route = null,
		$custom_dynamic = false,
		$baseurl = null
	) {
		return new static($api_def, $method, $call_uri, $raml_route, $custom_dynamic, $baseurl);
	}
	
	/**
	 * Construct a new API call object
	 *
	 * @param \Raml\ApiDefinition $api_def	The \Raml\APIDefinition object to work with
	 * @param string $method				The method for the call
	 * @param string $call_uri				The URI for the call (Without an internal prefix)
	 * @param string $raml_route			The URI as set in the RAML schema (With internal prefix)
	 *
	 * @return mixed The completed object or the array of error response data
	 */
	public function __construct(
		\Raml\ApiDefinition $api_def,
		$method,
		$call_uri,
		$raml_route = null,
		$custom_dynamic = false,
		$baseurl = null
	) {
		// If we're not parsing a static or dynamic call, then we can use the call URI as is.
		if (empty($raml_route)) {
			$raml_route = $call_uri;
		}
		
		// Set the basics for every other method to use.
		$this->call_uri			= $call_uri;
		$this->api_def			= $api_def;
		$this->method			= \Str::upper($method);
		$this->raml_route		= $raml_route;
		$this->custom_dynamic	= $custom_dynamic;
		$this->url				= $baseurl;
		
		if ($this->set_url($call_uri) === false) {
			return $this;
		}
		
		if ($this->set_headers() === false) {
			return $this;
		}
		
		if ($this->set_method_params() === false) {
			return $this;
		}
		
		// If we need a body, but we don't have one, we'll fail the call.
		if (empty($this->get_method_params()) && !in_array($this->method, array('GET', 'HEAD'))) {
				
			$status = \V1\APIRequest::is_static() === true ? 500 : 400;
			$this->errors = \Utility::format_error($status, \V1\Err::BAD_BODY, \Lang::get('v1::errors.bad_body'));
			return false;
				
		}
		
		// Don't waste time.
		if (\V1\APIRequest::get('api') !== 'custom') {
				
			if ($this->set_security_calls() === false) {
				return $this;
			}
			
			if ($this->replace_credentials() === false) {
				return $this;
			}
			
		}
		
		return $this;
	}
	
	/**
	 * Get the \Raml\APIDefinition for the current API.
	 * 
	 * @return \Raml\APIDefinition
	 */
	public function get_api_def()
	{
		return $this->api_def;
	}
	
	/**
	 * Get the parsed URL for the call
	 * 
	 * @return string The call URL
	 */
	public function get_url()
	{
		return $this->url;
	}
	
	/**
	 * Get just the URI (resource)
	 * 
	 * @return string The URI
	 */
	public function get_uri()
	{
		return $this->call_uri;
	}
	
	/**
	 * Get the query params for the URL
	 * 
	 * @return array
	 */
	public function get_query_params()
	{
		return $this->query_params;
	}
	
	/**
	 * Get the unaltered RAML URI (Will contain {static-call}/XXX for static calls)
	 * @return string
	 */
	public function get_raml_route()
	{
		return $this->raml_route;
	}
	
	/**
	 * Get the array of headers for the call
	 * 
	 * @return array The array of headers or an empty array
	 */
	public function get_headers()
	{
		return $this->headers;
	}
	
	/**
	 * Get the call method
	 * 
	 * @return string The call method
	 */
	public function get_method()
	{
		return $this->method;
	}
	
	/**
	 * Get the array of method params
	 * 
	 * @return mixed The array of method params, an empty array, or the string used for the body
	 */
	public function get_method_params()
	{
		return $this->method_params;
	}
	
	/**
	 * Get the body encoding type
	 * 
	 * @return string The body encoding type
	 */
	public function get_body_type()
	{
		return $this->body_type;
	}
	
	/**
	 * Get the array of call data for each security call
	 * 
	 * @return array The array of \V1\ApiCall objects for security calls, or an empty array
	 */
	public function get_security_calls()
	{
		return $this->security_calls;
	}
	
	/**
	 * Return the array of error data
	 * 
	 * @return array
	 */
	public function get_errors()
	{
		return $this->errors;
	}
	
	/**
	 * Return the array of credential data for the calls
	 * 
	 * @return array
	 */
	public function get_credentials()
	{
		return $this->credentials;
	}
	
	/**
	 * PROTECTED PARTS
	 */
	
	/**
	 * Set the full URL for an API call.
	 */
	protected function set_url()
	{
		if (empty($this->url)) {
			$base_uri = $this->api_def->getBaseUrl();
		} else {
			$base_uri = $this->url;
		}
		
		// Prevent endless loops.
		foreach (\Config::get('engine.forbidden_domains', array('api.bitapihub.com')) as $domain) {
			
			if (substr_count($base_uri, $domain) > 0) {
				
				$this->errors = \Utility::format_error(500, \V1\Err::BAD_DOMAIN, \Lang::get('v1::errors.bad_domain'));
				return false;
				
			}
			
		}
		
		// Custom calls cannot use the non-existant RAML data for the call.
		if (\V1\APIRequest::get('api', false) !== 'custom') {
			
			if ($this->custom_dynamic === true) {
				$route = $this->api_def;
			} else {
				$route = $this->api_def->getResourceByUri($this->raml_route)->getMethod($this->method);
			}
			
			// Base URI Parameters
			foreach ($route->getBaseUriParameters() as $named_parameter => $named_parameter_obj) {
				$base_uri = str_replace('{'.$named_parameter.'}', $named_parameter_obj->getDefault(), $base_uri);
			}
			
		}
	
		/**
		 * Protocols
		 */
	
		// Force HTTPS only on integrated APIs stating that they have that capability.
		if (\V1\APIRequest::get('api') !== 'custom') {
			
			// If the URL is an HTTP URL, but it supports HTTPS, we'll use HTTPS.
			if (substr($base_uri, 0, 7) === 'http://' && $route->supportsHttps()) {
				$base_uri = 'https://'.substr($base_uri, 7);
			}
			
		} else {
			
			$protocols = $this->api_def->getProtocols();
			$url_protocol = \Str::upper(parse_url($base_uri, PHP_URL_SCHEME));
			
			// No protocol specified, so we default to HTTP since every server speaks it.
			if (empty($url_protocol)) {
				$base_uri = 'http://'.$base_uri;
			} elseif (!in_array($url_protocol, $protocols)) {
				
				// Invalid protocol specified.
				$this->errors = \Utility::format_error(
					400,
					\V1\Err::BAD_PROTOCOL,
					\Lang::get('v1::errors.bad_protocol', array(
						
						'protocols'	=> implode(', ', $protocols),
						
					)));
				
				return false;
				
			}
			
		}
	
		$query_string = null;
		
		// $query_params is used only for validation, so we don't need it on custom dynamic calls.
		if ($this->custom_dynamic === false) {
			$this->query_params = $this->api_def->getResourceByUri($this->raml_route)->getMethod($this->method)->getQueryParameters();
		} else {
			$this->query_params = array();
		}
		
		// If we have an error or unusable data, then fail.
		if (!is_array($this->query_params = static::validate_params($this->query_params, 'query'))) {
			return false;
		}
		
		// We'll need to fix some symbols.
		if (!empty($this->query_params)) {
			$query_string = '?'.http_build_query($this->query_params, null, '&');
		}
		
		// Set the parsed URL with the call URI and any query parameters attached.
		$this->url = $base_uri.$this->call_uri.$query_string;
	}
	
	/**
	 * Set a query param manually without further validation.
	 * 
	 * @param string $variable	The variable to set
	 * @param string $value		The value to set for $variable
	 */
	public function set_query_param($variable, $value)
	{
		if (strpos($this->url, '?') === false) {
			$this->url .= '?';
		}
		
		$this->url .= strpos($this->url, '?') === false ? '?' : '&';
		
		// Keep the same encoding as with http_build_query()
		$this->url .= urlencode($variable).'='.urlencode($value);
	}
	
	/**
	 * Create an array of header data as defined by the RAML schema.
	 */
	protected function set_headers()
	{
		if ($this->custom_dynamic === false) {
			$headers = $this->api_def->getResourceByUri($this->raml_route)->getMethod($this->method)->getHeaders();
		} else {
			$headers = array();
		}
	
		// If we have an error or unusable data, then fail.
		if (!is_array($header_array = static::validate_params($headers, 'headers'))) {
			return false;
		}
	
		// Security calls may add header entries.
		$this->headers = array_merge($header_array, $this->headers);
	}
	
	/**
	 * Manually set a header value without futher validation.
	 * 
	 * @param string $header_name	The name of the header attribute
	 * @param string $header_value	The value to set for $header_name
	 */
	public function set_header($header_name, $header_value)
	{
		$this->headers[$header_name] = $header_value;
	}
	
	/**
	 * Create the array of request data to send by default as per the RAML schema.
	 * 
	 * @return bool True on succes, false on fail
	 */
	protected function set_method_params()
	{
		// These don't have body params, so let's save some processing power.
		if (in_array($this->method, array('GET', 'HEAD'))) {
			return true;
		}
		
		// Skip the validation, and gather our data to move on.
		if ($this->custom_dynamic === true) {
			
			$this->body_type = 'application/json';
				
			if (\V1\APIRequest::get('configure.basenode', false) !== false) {
				$this->body_type = 'application/xml';
			}
			
			if (\V1\APIRequest::get('api') === 'custom') {
				
				// The custom call has a media-type set,
				if (($media_type = \V1\APIRequest::get('configure.media-type', false)) !== false) {
					$this->body_type = $media_type;
				}
				
			} else {
				
				// Use the configured media type if we have one.
				if (!empty($this->api_def->getDefaultMediaType())) {
					$this->body_type = $this->api_def->getDefaultMediaType();
				}
				
			}
			
			if (($this->method_params = static::validate_params(array(), 'body')) === false) {
				return false;
			}
			
			return true;
			
		}
		
		$method_obj	= $this->api_def->getResourceByUri($this->raml_route)->getMethod($this->method);
		
		foreach ($method_obj->getBodies() as $body_type => $body_obj) {
			
			// We can't use the requested format
			if (($valid_format = \Utility::get_format($body_type)) === false) {
				continue;
			}
			
			// We only check the data for multipart/form-data and application/x-www-form-urlencoded
			if (in_array($valid_format, array('form-data', 'urlencoded'))) {
				
				$body_params = $method_obj->getBodyByType($body_type)->getParameters();
				
				// Security
				if (\Utility::is_serialized($body_params) === true || !is_array($body_params)) {
					
					$this->errors = \Utility::format_error(500, \V1\Err::BAD_BODY, \Lang::get('v1::errors.bad_body'));
					return false;
					
				}
				
				/*
				 * If we have an array, we'll use this body. If it failed to find all required parameters, then
				 * perhaps a different call type uses the ones posted to the system. We don't run is_serialized()
				 * again since \V1\APIRequest::setup() does that.
				 */
				if (is_array($body_array = static::validate_params($body_params, 'body'))) {
					
					$this->body_type = $body_type;
					$this->method_params = $body_array;
					return true;
					
				}
			
			} elseif (in_array($valid_format, array_keys(\Config::get('engine.supported_formats', array())))) {
				
				// If the format in the RAML schema is one we can process, we'll use it.
				
				if (\V1\APIRequest::is_static() === false) {
					
					// Dynamic calls use whatever is in the posted "configure.body" value as their method params.
					
					// Nothing to post and we need to post something since we have a body.
					if (
						\V1\APIRequest::get('configure', false) === false ||
						!is_array($posted = \V1\APIRequest::get('configure', false)) ||
						empty($posted['body']) ||
						\Utility::is_serialized($posted['body']) === true
					) {
						
						// We don't need to keep looping since we already know we'll never post anything.
						$this->errors = \Utility::format_error(400, \V1\Err::BAD_BODY, \Lang::get('v1::errors.bad_body'));
						return false;
						
					}
					
					// We can post in these formats.
					if (is_string($posted['body']) || is_array($posted['body'])) {
						
						// Validate the data entered against the supplied schema.
						if ($this->validate_schema($body_obj, $posted['body'], $body_type) === false) {
							
							$this->errors = \Utility::format_error(
								400,
								\V1\Err::SCHEMA_VAL_FAIL,
								\Lang::get('v1::errors.schema_val_fail')
							);
							continue;
							
						}
						
						$this->errors			= array();
						$this->method_params	= $posted['body'];
						$this->body_type		= $body_type;
						return true;
						
					}
					
				} else {
					
					// Static calls use the example data
					
					// No example, no post.
					if (empty($example = $method_obj->getBodyByType($body_type)->getExample())) {
						continue;
					}
					
					// Security
					if (\Utility::is_serialized($example) === true) {
							
						$this->errors = \Utility::format_error(400, \V1\Err::BAD_BODY, \Lang::get('v1::errors.bad_body'));
						return false;
							
					}
					
					// Validate the data entered against the supplied schema.
					if ($this->validate_schema($body_obj, $example, $body_type) === false) {
						
						$this->errors = \Utility::format_error(
							500,
							\V1\Err::SCHEMA_VAL_FAIL,
							\Lang::get('v1::errors.schema_val_fail')
						);
						continue;
						
					}
					
					$this->errors			= array();
					$this->method_params	= $example;
					$this->body_type		= $body_type;
					return true;
					
				}
				
			}
			
		}
		
		// No body types matched the data entered.
		if (empty($this->errors)) {
			
			// If we didn't set it yet, then we just didn't have a proper body.
			$this->errors = \Utility::format_error(500, \V1\Err::INVALID_BODY_TYPE, \Lang::get('v1::errors.invalid_body_type'));
			
		}
		
		return false;
	}
	
	/**
	 * Configure the security calls needed to make the call possible.
	 * 
	 * @return boolean True if the calls configured properly, or false if they weren't.
	 */
	protected function set_security_calls()
	{
		/**
		 * SECURITY 101 - How it works on Bit API Hub
		 * 
		 * There are a few different security strategies we use to authenticate with the remote server.
		 * 
		 * 1. Calls to servers that use our authentication method - Integrated APIs can easily authenticate
		 *    people on their system with the following:
		 *    
		 *    - X-AOE-Secret header		- A unique key specific to their API
		 *    - X-AOE-Account header	- A unique key specific to the developer account as used for the API
		 *    							  API providers should allow their account holders to enter this
		 *    							  key in their account area to tie the two together.
		 *    
		 *    These calls do not use securedBy as the authentication headers are automatically sent with each request.
		 *    
		 * 2. Calls to unintegrated APIs through custom calls, or calls to APIs of which the provider doesn't
		 *    wish to use our authentication method may use their own authentication method in which they require
		 *    our account holder to register with their API. The API Provider must assign Bit API Hub credentials
		 *    to use for static calls. For dynamic calls, keys may be posted with the request, or stored on our
		 *    servers.
		 *    
		 * 3. Calls which just use basic API key authentication may be used in conjunction with the above two types,
		 *    or alone. The second point above describes how API provider keys are obtained. set_credentials()
		 *    handles credential entry for variable based authentication, such as API keys.
		 *    
		 * NOTE: Digest auth, and some other authentication methods are not fully compatible with Data Calls.
		 * The reason for that is because some headers might need to get a custom value passed to sequential calls.
		 * (Ex. "nonce" will be different for each call, and that's generated by the remote server.) Our asynchronous
		 * batch processing makes processing multiple calls using those security schemes impossible for more than one
		 * call to the API during a Data Call at once.
		 */
		
		// Grab the needed credentials. If they posted a non-string credential, we'll flip them off.
		if ($this->set_credentials() === false) {
			return false;
		}
		
		/**
		 * Find out if we have a securedBy entry. Dynamic calls that specify a URI may not appear in the RAML,
		 * so for now we return true to avoid making any security calls.
		 * 
		 * @TODO Factor in the main API's securedBy
		 */
		try {
			
			$resource	= $this->api_def->getResourceByUri($this->raml_route);
			$method		= $resource->getMethod($this->method);
			if (empty($secured_by = $method->getSecuritySchemes())) {
					
				if (empty($secured_by = $resource->getSecuritySchemes())) {
					$secured_by = $this->api_def->getSecuredBy();
				}
					
			}
			
		} catch (\Raml\Exception\BadParameter\ResourceNotFoundException $e) {
			return true;
		}
		
		// If we have a security scheme that secured the whole API, then start messing about with it.
		if (!empty($secured_by)) {
			
			/*
			 * If it's the "null" schema, we can send the call without any authorization. Don't bother with
			 * further config.
			 */
			if (isset($secured_by['null'])) {
				return true;
			}
			
			// No credentials? We'll fail the call.
			if (empty($this->credentials)) {
				
				$this->errors = \Utility::format_error(401, \V1\Err::NO_CREDENTIALS, \Lang::get('v1::errors.no_credentials'));
				return false;
					
			}
			
			$secured = false;
			
			foreach ($secured_by as $schema_name => $securityscheme_obj) {
				
				/**
				 * NOTE: When a securedBy is attached to a method, it merges the data with the main API
				 * call, so we don't process those here. When the securedBy is attached to the root of
				 * the schema, then it secures the whole API. That's what we're processing now.
				 * 
				 * @link http://raml.org/spec.html#usage-applying-a-security-scheme-to-an-api
				 * 
				 * Credentials are parsed in with variables. @See \V1\APICall\replace_credentials()
				 */
				
				/**
				 * Take care of any mergers for custom security schemes using the same URL that we're on.
				 * <current-url> is a literal string set on the "url" key. That means that every URL
				 * used recieves the merged fields without having to have a securedBy attribute on the method.
				 */
				if (
					$this->custom_dynamic === false &&
					substr($securityscheme_obj->getType(), 0, 2) === 'x-' &&
					is_array($settings = $securityscheme_obj->getSettings()) &&
					is_string($settings['url'])
				) {
					
					if ($settings['url'] === '<current-url>') {
						
						$method->addSecurityScheme($securityscheme_obj, true);
						$secured = true;
						
					} else {
							
						/*
						 * Custom configuration - Use the provided security call data to call
						 * one extra URL with the provided information.
						 */
						
						if (is_object($scheme_call = \V1\SecurityCall\Custom::forge()->run($securityscheme_obj, $this))) {
						
							if ($scheme_call->error === true) {
								continue;
							}
						
						} elseif (is_array($scheme_call)) {
							
							$this->errors = $scheme_call;
							return false;
							
						} else {
							$secured = true;
						}
						
					}
					
				} else {
					
					/*
					 * Configure the call if we can, but only allow one security structure API
					 * call to prevent abuse. In the future we may allow for more.
					 */
					if (count($this->security_calls) === 0) {
						
						$class_name = str_replace(array('_', '\\'), '', $schema_name);
						if (
							$class_name !== 'custom' &&
							class_exists('\V1\SecurityCall\\'.str_replace('_', '', $class_name))
						) {
							
							$scheme_call_obj = call_user_func('\V1\SecurityCall\\'.$class_name.'::forge');
							$scheme_call = $scheme_call_obj->run($securityscheme_obj, $this);
							
							/*
							 * Return messages used for the next step in the security measures, such as
							 * OAuth authentication.
							 */
							if (is_array($scheme_call)) {
								
								$this->errors = $scheme_call;
								return false;
								
							}
							
							if (is_object($scheme_call)) {
								
								if ($scheme_call->error === true) {
									continue;
								}
								
								$this->security_calls[] = $scheme_call;
								$secured = true;
								
							}
							
							if ($scheme_call === true) {
								
								// Nothing to use later.
								$secured = true;
								
							}
						
						}
						
					}
					
				}
				
			}
			
			// We had security calls we couldn't process, so we fail.
			if ($secured === false) {
				
				$this->errors = \Utility::format_error(500, \V1\Err::BAD_SECURITY, \Lang::get('v1::errors.bad_security'));
				return false;
			}
			
		}
		
		return true;
	}
	
	/**
	 * Grab the credentials for the API call
	 * 
	 * @return True if we set the credentials, or false if not.
	 */
	protected function set_credentials()
	{
		if (($credentials = \V1\Keyring::get_credentials()) === false) {
			
			$this->errors = \Utility::format_error(400, \V1\Err::CRED_NOT_STRING, \Lang::get('v1::errors.cred_not_string'));
			return false;
			
		}
		
		$this->credentials = $credentials;
		return true;
	}
	
	/**
	 * Replace credential data variables for any credentials meant to be sent with the API call.
	 * 
	 * NOTE: This functionality is very basic. It doesn't do anything fancy like signing requests.
	 * If there's a demand for that later, we can consider that functionality.
	 */
	protected function replace_credentials()
	{
		// If we have an array, start crackin'.
		if (!empty($this->credentials)) {
			
			foreach($this->credentials as $variable => $replacement) {
				
				// Query params
				$this->url				= str_replace(urlencode($variable), urlencode($replacement), $this->url);
				
				// Main URL
				$this->url				= str_replace($variable, $replacement, $this->url);
				$this->headers			= str_replace($variable, $replacement, $this->headers);
				$this->method_params	= str_replace($variable, $replacement, $this->method_params);
				
			}
			
		}
		
		// Perhaps a variable was replaced with a CRLF in it. We'll need to prevent the call.
		if ($this->check_crlf($this->headers) === false) {
			return false;
		}
	}
	
	/**
	 * Validate that the required parameters have values
	 * 
	 * @param array $params	The array of parameters to check
	 * @param string $type	The name of the \V1\APIRequest::get('configure') key to use for user data (can be
	 * 						null for static calls)
	 * 
	 * @return mixed The array of finalized parameters, or false on fail
	 */
	protected function validate_params(array $params, $type = null)
	{
		$param_array = array();
		$error_code = 500;
		
		if (\V1\APIRequest::is_static() === false) {
		
			// Dynamic call data
			$configure = \V1\APIRequest::get('configure');
			$param_array = isset($configure[$type]) ? $configure[$type] : array();
			$error_code = 400;
		
		}
		
		// Skip the validation on non-validation requests
		if ($this->custom_dynamic === false) {
			
			// Loop and parse
			foreach ($params as $param_name => $namedparameter_obj) {
					
				// Use the default if it exists if we have an empty value.
				if (
					!array_key_exists($param_name, $param_array) &&
					!empty($default = $namedparameter_obj->getDefault())
				) {
					$param_array[$param_name] = $default;
				}
					
				// Further validate the param based on RAML specs (Including checking if it's required)
				try {
					
					// Set it to a separate variable to avoid empty values getting sent to the server.
					$validate = empty($param_array[$param_name]) ? null : $param_array[$param_name];
					$namedparameter_obj->validate($validate);
					
				} catch (\Raml\Exception\ValidationException $e) {
			
					$this->errors = \Utility::format_error(
						$error_code,
						\V1\Err::BAD_PARAM,
						\Lang::get(
							'v1::errors.bad_param',
							array(
								'required_param'	=> $param_name,
								'location'			=> $type,
							)
						)
					);
					return false;
			
				}
					
			}
			
		}
			
		// Prevent CRLF attacks
		if (!empty($param_array) && $type === 'headers') {
			
			if ($this->check_crlf($param_array) === false) {
				return false;
			}
			
		}
		
		return $param_array;
	}
	
	/**
	 * Validate a certain formats against their schema.
	 * 
	 * @param \Raml\Body $body_obj	The body object to pull the schema from
	 * @param mixed $data			The data used as the body
	 * @param string $body_type		The mime type of the chosen RAML body
	 * 
	 * @return boolean True if the validation succeeded or wasn't performed, or false if validation failed
	 */
	protected function validate_schema(\Raml\Body $body_obj, $data, $body_type)
	{
		$format = \Utility::get_format($body_type);
		
		/*
		 * If they've explicitly specified the request in the remote server's format, then we'll validate it.
		 * If they've posted an array of data, then we'll just hope for the best later on when we auto-convert it.
		 */
		if (is_string($data) && !empty($schema = $body_obj->getSchema())) {
			
			// Validate it against types we can validate against.
			switch ($format) {
				
				case 'json':
					// No break
				case 'xml':
					
					try {
						$schema->validate($data);
					} catch (\Raml\Exception\InvalidSchemaException $e) {
						return false;
					}
					
					break;
					
				case 'csv':
					/**
					 * We don't yet have a way to check this type.
					 * 
					 * @TODO Get one.
					 */
					break;
				
			}
			
		}
		
		/*
		 * Either it was successful, or we can't validate it due to the specified data not falling into
		 * our limited 3x3 mindspace. :p
		 * 
		 * You can't process everything, so you've got to process... some. Hmmm.
		 */
		return true;
	}
	
	/**
	 * Prevent CRLF attacks
	 * 
	 * @param array $header_array The array of headers
	 * @return boolean True if we're safe, false if not
	 */
	protected function check_crlf(array $header_array)
	{
		foreach ($header_array as $header_name => $header_val) {
				
			if (
				substr_count($header_val, "\n") > 0 ||
				substr_count($header_val, "\r") > 0 ||
				substr_count($header_val, '\n') > 0 ||
				substr_count($header_val, '\r') > 0
			) {
		
				$this->errors = \Utility::format_error(400, \V1\Err::NEW_LINE_HEADER, \Lang::get('v1::errors.new_line_header'));
				return false;
		
			}
			
		}
		
		return true;
	}
}

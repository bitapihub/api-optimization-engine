<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Runs multiple API requests at once
 */

namespace V1;

class Socket {
	
	/**
	 * The instances of this object
	 * @var object
	 */
	private static $instances = null;
	
	/**
	 * The array of queued call streams
	 * @var array
	 */
	private $streams = array();
	
	/**
	 * Holds the values from \V1\APIRequest::get() for the calls made
	 * @var array
	 */
	private $api_request = array();
	
	/**
	 * Holds the value for \V1\APIRequest::is_static() for each call
	 * @var array
	 */
	private $is_static = array();
	
	/**
	 * The array of \V1\APICall objects
	 * @var array
	 */
	private $api_call = array();
	
	/**
	 * Create a new instance.
	 * 
	 * @return object
	 */
	public static function forge($instance = '_default_')
	{
		if (empty(static::$instances[$instance])) {
			static::$instances[$instance] = new static;
		}
		
		return static::$instances[$instance];
	}
	
	/**
	 * Queue a call
	 * 
	 * @param string $api		The name of the API we're calling
	 * @param array $call_data	The array of call data for the call
	 * @param \V1\APICall $apicall_obj	The APICall object for the current call
	 * 
	 * @return bool True on success, or false on fail
	 */
	public function queue_call($api, array $call_data, \V1\APICall $apicall_obj)
	{
		// We need a valid URL
		if (
			($parsed_url = parse_url($call_data['url'])) === false ||
			empty($parsed_url['scheme']) ||
			empty($parsed_url['host'])
		) {
			
			$this->streams[$api][] = \Utility::format_error(500);
			return false;
			
		}
		
		/**
		 * Configure
		 */
		$parsed_url['path']		= empty($parsed_url['path']) ? '/' : $parsed_url['path'];
		$parsed_url['query']	= empty($parsed_url['query']) ? null : '?'.$parsed_url['query'];
		$call_data['method']	= empty($call_data['method']) ? 'GET' : \Str::upper($call_data['method']);
		$stream_scheme			= null;
		
		if ($parsed_url['scheme'] === 'https') {
		
			$parsed_url['port']	= empty($parsed_url['port']) ? '443' : $parsed_url['port'];
			$stream_scheme		= 'ssl://';
		
		} else {
		
			$parsed_url['port']	= empty($parsed_url['port']) ? '80' : $parsed_url['port'];
		
		}
			
		$opts['http'] = array(
			
			'method' 			=> $call_data['method'],
			'ignore_errors'		=> true,
			'protocol_version'	=> 1.1,
			
		);
			
		/**
		 * Request
		*/
		$request = $call_data['method']." ".$parsed_url['path'].$parsed_url['query']." HTTP/1.1\r\n";
		$request .= "Host: ".$parsed_url['host'].":".$parsed_url['port']."\r\n";
		$request .= "Accept-Encoding: gzip, deflate\r\n";
			
		// User headers
		if (!empty($call_data['headers']) && is_array($call_data['headers'])) {
		
			foreach ($call_data['headers'] as $header_name => $header_val) {
					
				$request .= $header_name.": ".$header_val."\r\n";
					
			}
		
		}
			
		/**
		 * Body
		 */
		if (
			!empty($call_data['body-type']) &&
			!empty($call_data['body']) &&
			!in_array($call_data['method'], array('GET', 'HEAD'))
		) {
		
			$build_body = static::build_body($call_data['method'], $call_data['body-type'], $call_data['body']);
			
			if (is_array($build_body)) {
				
				// Content-Type and Content-Length headers and our body
				$request .= $build_body['header']."\r\n";
				$request .= "Content-Length: ".strlen($build_body['body'])."\r\n\r\n";
				$request .= $build_body['body']."\r\n";
				
			} else {
				
				// We need to post, but we can't.
				if (\V1\APIRequest::is_static() === true) {
					
					$this->streams[$api][] = \Utility::format_error(
						400,
						\V1\Err::BAD_FORMAT,
						\Lang::get('v1::errors.bad_format_static')
					);
					
				}
					
				$this->streams[$api][] = \Utility::format_error(
					400,
					\V1\Err::BAD_FORMAT,
					\Lang::get('v1::errors.bad_format')
				);
				
				return false;
			
			}
		
		} else {
			
			// Finish our request
			$request .= "\r\n";
			
		}
		
		/**
		 * CALLBACKS
		 */
		if (!empty($security_calls = $apicall_obj->get_security_calls())) {
			
			foreach ($security_calls as $security_call) {
				
				// Process the request data as needed.
				$request = $security_call->before_send($request);
				
			}
			
		}
		
		$context = stream_context_create($opts);
			
		try {
			
			$stream = stream_socket_client(
				$stream_scheme.$parsed_url['host'].":".$parsed_url['port'],
				$errno,
				$errstr,
				\Config::get('v1::socket.timeout', 5),
				STREAM_CLIENT_ASYNC_CONNECT|STREAM_CLIENT_CONNECT,
				$context
			);
			
		} catch (\Exception $e) {
			
			// Server unavailable
			$this->streams[$api][] = \Utility::format_error(
				503,
				\Err::SERVER_ERROR,
				\Lang::get('v1::errors.remote_unavailable')
			);
			return false;
			
		}
		
		$this->api_request[$api][]	= \V1\APIRequest::get();
		$this->is_static[$api][]	= \V1\APIRequest::is_static();
		$this->api_call[$api][]	= $apicall_obj;
		
		if ($stream !== false) {
		
			fwrite($stream, $request);
			$this->streams[$api][] = &$stream;
			return true;
		
		} else {
			
			// Server unavailable
			$this->streams[$api][] = \Utility::format_error(
				503,
				\Err::SERVER_ERROR,
				\Lang::get('v1::errors.remote_unavailable')
			);
			return false;
			
		}
	}
	
	/**
	 * Get the results from all of our API calls.
	 * 
	 * @return mixed The array of response data, or false on fail
	 */
	public function get_results()
	{
		/**
		 * NOTE: Do not use \V1\APIRequest from this point on, as Data Calls will only call this method
		 * once, thus leaving us with the data from the last API call the Data Call ran. Instead, use
		 * $this->api_request[$api][$api_call_number]
		 */
		$responses		= array();
		$sockets_array	= array();
		
		foreach ($this->streams as $api => $sockets) {
			
			// Make sure we only have resources
			foreach ($sockets as $socket_id => $socket_to_me_baby) {
				
				// May be an error
				if (!is_resource($socket_to_me_baby)) {
				
					$responses[$api][$socket_id] = false;
					continue;
				
				}
				
				$sockets_array[$socket_id] = &$this->streams[$api][$socket_id];
				
			}
			
			$read		= &$sockets_array;
			$write		= null;
			$exception	= null;
			
			// Nothing to process
			if (empty($sockets_array)) {
				
				$this->streams = array();
				return false;
				break;
				
			}
			
			/**
			 * NOTE: stream_select() will continually update the read stream! As the data comes in, it's made
			 * available. That means that when you try to access data from the stream, the stream may not have
			 * finished yet, so not all data will be present. Use the Content-Length header to figure out how
			 * much data there is.
			 */
			stream_select($read, $write, $exception, \Config::get('v1::socket.timeout', 5));
			
			if (count($read)) {
		
				foreach ($read as &$read_stream) {
					
					$id = array_search($read_stream, $sockets_array);
					
					$response = explode("\r\n\r\n", fread($read_stream, 8192));
					
					// Make sure that we have all of the headers.
					while (empty($response[1])) {
						
						$response = $response[0].fread($read_stream, 8192);
						$response = explode("\r\n\r\n", $response);
						
					}
					
					$headers = static::build_headers(explode("\r\n", $response[0]));
					unset($response[0]);
					$response[1] = implode("\r\n\r\n", $response);
					$status_code = $headers['BAH-STATUS'];
					unset($headers['BAH-STATUS']);
					
					// We need to know the length before we can start pulling.
					if (!empty($headers['Content-Length'])) {
						
						while (strlen($response[1]) < (int) $headers['Content-Length']) {
							$response[1] .= fread($read_stream, 8192);
						}
						
						// Decode the body
						if (!empty($headers['Content-Encoding'])) {
							$response[1] = static::decode_body($response[1], $headers);
						}
						
					} elseif (!empty($headers['Transfer-Encoding']) && $headers['Transfer-Encoding'] === 'chunked') {
						
						$lines = array_reverse(explode("\r\n", $response[1]));
						
						// Check if we've already finished pulling data.
						if (
							count($lines) < 3 ||
							!(
								count($lines) >= 3 &&
								strlen($lines[0]) === 0 &&
								strlen($lines[1]) === 0 &&
								strlen($lines[2])  === 1 &&
								is_numeric($lines[2]) &&
								(int) $lines[2] === 0
							)
						) {
							
							/**
							 * @TODO If the chunking is corrupt, then the loop won't break, and fread will
							 * try to pull data that it can't, and it'll make the script hang. Perhaps
							 * there's a better way around the fact that we can't tell if a live socket is
							 * at the end for some reason?
							 */
							while (1) {
	
								$response[1] .= fread($read_stream, 8192);
	
								// Check if we've finished the chunking.
								$lines = array_reverse(explode("\r\n", $response[1]));
								if (
									count($lines) >= 3 &&
									strlen($lines[0]) === 0 &&
									strlen($lines[1]) === 0 &&
									strlen($lines[2])  === 1 &&
									is_numeric($lines[2]) &&
									(int) $lines[2] === 0
								) {
									break;
								}
	
							}
							
						}
						
						// Decode the body
						$response[1] = static::decode_body($response[1], $headers);
						
					} else {
						
						$response[1] = false;
						
					}
					
					// We won't allow remote APIs to send us a serialized response for security reasons.
					if ($response[1] === false || \Utility::is_serialized($response[1]) === true) {
					
						$responses[$api][$id] = \Utility::format_error(
							500,
							\V1\Err::BAD_RESPONSE,
							\Lang::get('v1::errors.bad_response')
						);
					
						continue;
					
					}
					
					$response_data = array(
							
						'status'	=> $status_code,
						'headers'	=> $headers,
						'body'		=> $response[1],
							
					);
					
					/**
					 * CALLBACKS
					 */
					$apicall_obj = $this->api_call[$api][$socket_id];
					if (!empty($security_calls = $apicall_obj->get_security_calls())) {
							
						foreach ($security_calls as $security_call) {
					
							// Process the response data as needed.
							$response_data = $security_call->after_response($response_data);
					
						}
							
					}
					
					// Prepare the response to show the customer.
					$responses[$api][$id] = static::prepare_response(
						$response_data,
						$this->api_request[$api][$id],
						$this->is_static[$api][$id]
					);
						
					fclose($read_stream);
						
				}
				
				// Avoid pointer location issues.
				unset($read_stream);
		
			} else {
		
				// A time-out means that *all* streams have failed to receive a response.
				$this->streams = array();
				return false;
				break;
		
			}
			
		}
		
		// Return the responses after we reset the stack.
		$this->streams = array();
		return $responses;
	}
	
	/**
	 * Convert an array of body data to the proper format
	 * 
	 * @param string $format	The format to convert to
	 * @param array $params		The array of body params to convert
	 * 
	 * @return mixed The string of converted body data, an array if we converted to multipart/form-data,
	 * 					or false on fail
	 */
	protected static function encode($format, array $params)
	{
		switch ($format) {
				
			case 'xml':
					
				\Config::load('format', true);
				$basenode = \Config::get('format.xml.basenode', 'xml');
		
				if (\V1\APIRequest::is_static() === false) {
					$basenode = \V1\APIRequest::get('configure.basenode', $basenode);
				}
		
				return \Format::forge($params)->to_xml(null, null, $base_node);
					
				break;
					
			case 'json':
				return \Format::forge($params)->to_json();
				break;
					
			case 'csv':
				return \Format::forge($params)->to_csv();
				break;
					
			// application/x-www-form-urlencoded
			case 'urlencoded':
				return http_build_query($params, null, '&');
				break;
		
			// multipart/form-data
			case 'form-data':
				return static::build_multipart($params);
				break;
		
		}
	}
	
	/**
	 * Build a multipart/form-data request
	 * 
	 * @param array $params The array of params to build the multipart/form-data
	 * @return mixed An array containing the boundary and body data, or false on fail
	 */
	protected static function build_multipart(array $params)
	{
		// Generate some crapulance that probably won't get repeated in the body.
		$boundary = 'BAH'.(int)microtime(true);
		
		$out	= null;
		$file	= false;
		foreach ($params as $param_name => $param_val) {
			
			$out .= '--'.$boundary."\r\n";
			
			/**
			 * We aren't using file data right now, but it's here to show how it's done.
			 */
			if ($file === true) {
				
				$out .= 'content-disposition: form-data; name="'.$param_name.'"; filename="FILE NAME HERE"'."\r\n";
				$out .= "Content-Type: MIME TYPE HERE\r\n";
				$out .= "Content-Transfer-Encoding: binary\r\n\r\n";
				$out .= "BINARY DATA HERE\r\n";
				
			} else {
				
				$out .= 'content-disposition: form-data; name="'.$param_name.'"'."\r\n\r\n";
				$out .= $param_val;
				
			}
			
		}
		
		// We need something to post.
		if (empty($out)) {
			return false;
		}
		
		$out .= '--'.$boundary.'--';
		
		// We'll form headers off of the boundary, so we need to return both.
		return array(
			
			'bondary'	=> $boundary,
			'body'		=> $out,
			
		);
	}
	
	/**
	 * Build the body for the request
	 * 
	 * @param string $method		The HTTP method verb
	 * @param string $body_type		The content type for the body
	 * @param mixed $params			The array of params to format, or a string of pre-formatted data
	 * 
	 * @return mixed True if we're in GET or HEAD mode, an array with the Content-Type header and body data, or
	 * 					false on fail
	 */
	protected static function build_body($method, $body_type, $params)
	{
		if (!in_array($method, array('GET', 'HEAD')) && !empty($body_type)) {
		
			// We have a body
			$header = 'Content-Type: '.$body_type;
			$body = null;
				
			if (is_string($params)) {

				$body = $params;
				
			} else {
					
				if (
					is_array($params) &&
					($format = \Utility::get_format($body_type)) !== false
				) {
						
					// Convert the body data
					$body = static::encode($format, $params);
					if (is_array($body) && !empty($body['boundary']) && !empty($body['body'])) {
		
						$header .= ', boundary='.$body['boundary'];
						$body = $body['body'];
		
					} elseif (!is_string($body)) {
						
						return false;
						
					}
						
				} else {
		
					return false;
						
				}
					
			}
			
			return array(
					
				'header'	=> $header,
				'body'		=> $body,
					
			);
		
		}
		
		// GET or HEAD
		return true;
	}
	
	/**
	 * Create an array from the header data
	 * 
	 * @param array $headers The array of headers from the response
	 * @return array The array of headers
	 */
	protected static function build_headers(array $headers)
	{
		// Find the status code.
		foreach ($headers as $header_key => $header_data) {
				
			if (
				substr(\Str::upper($header_data), 0, 5) === 'HTTP/' &&
				substr_count($header_data, ':') === 0
			) {
		
				$http_vers_explode = explode(' ', $header_data);
				if (
					is_numeric($http_vers_explode[1]) &&
					strlen((int)$http_vers_explode[1]) === 3
				) {
					$status_code = (int)$http_vers_explode[1];
					$headers['BAH-STATUS'] = $status_code;
					unset($headers[$header_key]);
				}
		
			} else {
		
				// Create an array with the header name as the key.
				$header_parts = explode(':', $header_data);
				$header_name = trim($header_parts[0]);
				unset($header_parts[0]);
				$headers[$header_name] = trim(implode(':', $header_parts));
				unset($headers[$header_key]);
		
			}
		
		}
		
		if (empty($headers['BAH-STATUS'])) {
			$headers['BAH-STATUS'] = 200;
		}
		
		return $headers;
	}
	
	/**
	 * Prepare the response to show the customer. We also handle caching and usage.
	 * 
	 * @param array $response	The response array containing data from the remote server
	 * @param string $api		The name of the API we're preparing the response for
	 * 
	 * @return array The response formatted string
	 */
	public static function prepare_response(array $response, $api_request = null, $is_static = null)
	{
		$api_request = empty($api_request) ? \V1\APIRequest::get() : $api_request;
		
		$api		= $api_request['api'];
		$is_static	= $is_static === null ? \V1\APIRequest::is_static() : $is_static;
		
		$api_data = \V1\Model\APIs::get_api($api);
		
		if ($is_static === true && $api_data['account_id'] === 0) {
			$response['headers'] = array();
		} else {
			$response['headers'] = static::sanitize_headers($response['headers']);
		}
		
		$response['body'] = static::convert_body($response['headers'], $response['body']);
		
		$response_array	= \Utility::format_response(200, $response);
		$internal_call	= \Utility::is_internal_call();
		
		/*
		 * Cache the static call response if it the remote server didn't report an error.
		 * To allow for proper testing, we don't cache calls from the account area or other internal
		 * locations.
		 */
		if ($is_static === true && $response['status'] < 300 && $internal_call === false) {
			
			$api_call = ($is_static === true) ? $api_request['static-call'] : null;
			\V1\Call\StaticCall::set_call_cache($response_array, $api, $api_call);
			
		}
		
		/*
		 * Log the usage stats if we aren't running a call from the internal API testing system. (Ex. The
		 * account area)
		 */
		if ($internal_call === false) {
				
			// Set the account usage stats
			\V1\Usage::set_usage($api);
				
			// Set the API provider stats
			\V1\Usage::set_api_stats($response['status'], $api_request, $is_static);
				
		}
		
		return $response_array;
	}
	
	/**
	 * Remove unsafe headers from the respose from the API Provider's server
	 * 
	 * @param array $headers The array of unfiltered headers
	 * @return array The array of filtered headers
	 */
	protected static function sanitize_headers(array $headers)
	{
		$safe_headers = array();
		
		$unsafe = \Config::get('engine.filter_headers');
		foreach ($headers as $header_name => $header_val) {
			
			if (!in_array($header_name, $unsafe)) {
				$safe_headers[$header_name] = $header_val;
			}
			
		}
		
		return $safe_headers;
	}
	
	/**
	 * Unholy body converter - Convert your body into an array.
	 * 
	 * @param array $headers	The array of response headers
	 * @param string $body		The body data to format
	 * 
	 * @return mixed The array of converted data, or the unaltered body string
	 */
	protected static function convert_body(array $headers, $body)
	{
		if (array_key_exists('Content-Type', $headers)) {
				
			/**
			 * Content-Type may have extra parameters.
			 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.7
			 */
				
			$mime_type = explode(';', $headers['Content-Type'])[0];
			$format = \Utility::get_format($mime_type);
			
			// The header was for an unsupported format.
			if ($format === false) {
				return $body;
			}
			
			// Keep namespaces
			$format = $format === 'xml' ? 'xml:ns' : $format;
			
			/**
			 * @TODO
			 *
			 * Forging to an array could be unsafe since objects are involved, but JSON
			 * uses objects in legitimate cases, so we can't block objects. If it becomes
			 * an issue, perhaps we can loop through the data and check if the object is
			 * of a class that already exists in our system? In that case we'll block it,
			 * but for now there's way too much work involved in verifying if there even
			 * is a threat.
			 *
			 * Let's let the system get hacked while we sit on our hands and do nothing.
			 * FireFox follows that methodology with memory management, and Microsoft insists
			 * on a forced reboot after every update instead of waiting patiently. So perhaps
			 * we should be dumb about our issues, too? It seems to be the in thing. (Sarcasm)
			 */
			// If we can't convert the data, then we'll spit it out as is.
			if (!in_array($format, array('form-data', 'urlencoded'))) {
				
				// Sometimes the conversion will fail in odd ways.
				try {
					return \Format::forge($body, $format)->to_array();
				} catch (\Exception $e) {
					return $body;
				}
				
			}
				
		} else {
				
			// We don't have a Content-Type header
			
			// Grab the supported formats list.
			$formats = array_keys(\Config::get('engine.supported_formats'));
			
			// Remove entries for stuff that doesn't pertain to \Format and keep the XML namespaces.
			$formats = array_diff($formats, array('xml', 'form-data', 'urlencoded'));
			$formats[] = 'xml:ns';
			
			// Loop and forge, and return the array if we guessed its type correctly.
			foreach ($formats as $key => $format_name) {
				
				try {
					
					if (!empty($body_array = \Format::forge($body, $format_name)->to_array())) {
						return $body_array;
					}
					
				} catch (\Exception $e) {}
				
			}
				
		}
		
		// What he said. (Return the remote server's response as is.)
		return $body;
	}
	
	/**
	 * Process a chuncked encoded response
	 * 
	 * The decunker brought to you by...
	 * @link http://stackoverflow.com/questions/9993622/how-to-decode-inflate-a-chunked-gzip-string#answer-9993999
	 * 
	 * @param string $str The chunked string
	 * @return mixed The dechunkeditty-chuncked string, or false on fail
	 */
	protected static function unchunk_string($str)
	{
		// A string to hold the result
		$result = '';
	
		// Split input by CRLF
		$parts = explode("\r\n", $str);
		
		// These vars track the current chunk
		$chunkLen = 0;
		$thisChunk = '';
	
		// Loop the data
		while (($part = array_shift($parts)) !== null) {
			
			if ($chunkLen) {
				
				// Add the data to the string
				// Don't forget, the data might contain a literal CRLF
				$thisChunk .= $part."\r\n";
				if (strlen($thisChunk) == $chunkLen) {
					// Chunk is complete
					$result .= $thisChunk;
					$chunkLen = 0;
					$thisChunk = '';
				} else if (strlen($thisChunk) == $chunkLen + 2) {
					// Chunk is complete, remove trailing CRLF
					$result .= substr($thisChunk, 0, -2);
					$chunkLen = 0;
					$thisChunk = '';
				} else if (strlen($thisChunk) > $chunkLen) {
					// Data is malformed
					return false;
				}
				
			} else {
				
				// If we are not in a chunk, get length of the new one
				if ($part === '') {
					continue;
				}
				
				if (!$chunkLen = hexdec($part)) {
					break;
				}
			}
			
		}
	
		// Return the decoded data of FALSE if it is incomplete
		return ($chunkLen) ? false : $result;
	}
	
	/**
	 * Decode a body possibly encoded with gzip or deflate, and optionally chunked.
	 * 
	 * Offsets from @link http://php.net/manual/en/function.gzinflate.php#112201
	 * 
	 * @param string $body		The body, possibly encoded
	 * @param array $headers	The array of response headers
	 * 
	 * @return mixed The decoded string, or false on fail
	 */
	protected static function decode_body($body, array $headers)
	{
		// Dechunking if needed
		if (!empty($headers['Transfer-Encoding']) && $headers['Transfer-Encoding'] === 'chunked') {
			$body = static::unchunk_string($body);
		}
		
		// If it's encoded, and not plain text, we decode that now.
		if (!empty($headers['Content-Encoding'])) {
			
			switch ($headers['Content-Encoding']) {
			
				case 'deflate':
					$body = gzinflate($body);
					try {
						$body = gzinflate(substr($body,2,-4));
					} catch (\PhpErrorException $e) {
						$body = false;
					}
					break;
						
				case 'gzip':
						
					try {
						$body = gzinflate(substr($body,10,-8));
					} catch (\PhpErrorException $e) {
						$body = false;
					}
						
					break;
			
				default:
					// We can't decode it.
					return false;
					break;
			
			}
			
		}
		
		return $body;
	}
}

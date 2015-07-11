<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * General configuration file for the API engine - Any custom configuration data that doesn't fit in anywhere else
 */

 return array(
 	
 	/**
 	 * How many seconds should we consider the call's timestamp valid for? This value is only set
 	 * to compensate for any latency in the request. Factor in the following:
 	 *
 	 * 1. File uploads (For now we don't allow direct file uploads through the API gateway.)
 	 * 2. Distance of the customer to the Bit API Hub DC
 	 * 3. The load of the client's server
 	 * 4. The load of the Bit API Hub cloud's server processing the call
 	 *
 	 * Remember that the more nonces we store for the timestamps, the more ram we take up to do so through Redis.
 	 * Nonces past the cutoff are removed to speed things along.
 	 */
 	'timestamp_cutoff'	=> 15,
 	
 	/**
 	 * List of Bit API Hub IP addresses to always have access to everyone's API calls
 	 */
 	'whitelisted_ips'	=> array(
 	
 		'127.0.0.1',
 	
 	),
 	
 	/**
 	 * When the IP used in running test calls from the account area is 127.0.0.1, what IP should
 	 * we tell the remote server that we're forwarding the testing call for?
 	 */
 	'call_test_ip'	=> '192.168.0.1',
 	
 	/**
 	 * List the domains the engine may not call.
 	 */
 	'forbidden_domains'	=> array(
 	
 		// 'example.com',
 	
 	),
 	
 	/**
 	 * Set to true to enable the following headers for every API request.
 	 * 
 	 * X-AOE-Secret - The secret ID associated with the API in your database (Useful for third-parties
 	 * 				  to recognize a request from you)
 	 * X-AOE-Account - The account ID in your database for the account making the call (Useful for third-parties
 	 * 				   to recognize a user on your system in conjunction with the API secret)
 	 * X-AOE-Version - The version of the API you're using to make the call (Useful for third-parties
 	 * 				   to help you (and the AOE team) to troubleshoot issues with the engine) The engine
 	 * 				   version is also part of the user-agent that the engine always sends.
 	 */
 	'send_engine_auth'	=> true,
 	
 	/**
 	 * Unless you're expecting a large amount of data in a response, then leave this setting at "true."
 	 * If your static calls start filling up your cache, try lowing your caching period threshold before
 	 * disabling your call cache.
 	 */
 	'cache_static_calls'	=> true,
 	
 	/**
 	 * Supported formats for converting to and from in all requests on remote servers
 	 */
 	'supported_formats'	=> array(
 		
 		'json' => array(
 			
 			'application/json',
 			'text/json',
 		
 		),
 		'xml' => array(
 			
 			'application/xml',
 			'text/xml',
 			'application/soap+xml',
 		
 		),
 		'csv' => array(
 			
 			'application/csv',
 			'text/csv',
 		
 		),
 		'form-data' => array(
 		
 			'multipart/form-data'
 			
 		),
 		'urlencoded' => array(
 		
 			'application/x-www-form-urlencoded'
 			
 		),
 	),
 	
 	/**
 	 * List of headers to remove from all remote server responses
 	 */
 	'filter_headers' => array(
 	
 		'Set-Cookie'
 	
 	),
 	
 	/**
 	 * Set to true if you don't want the script to run calls against the remote server, but instead
 	 * return dummy data. That avoids issues with remote server API quotas. (Does not effect security
 	 * calls)
 	 */
 	'dev_disable_live_calls'	=> false,
 	
 	/**
 	 * Every time an API is called, a DB row gets an update. Set this entry to "false" to disable tracking
 	 * API usage stats.
 	 */
 	'track_usage_stats'		=> false,
 	
 	/**
 	 * To prevent massive amounts of stats in the DB, we'll log the number of calls
 	 * for each HTTP status code in the specified duration of time in minutes. 
 	 */
 	'api_stats_increment'	=> 30,
 	
 	/**
 	 * The redirect URL to guide people on how to add the retrieved OAuth 1.0a data to the
 	 * request on the API engine. (People can supply their own.)
 	 */
 	'oauth_10a_callback_url'	=> 'https://example.com/oauth10a',
 	
 	/**
 	 * The redirect URL to guide people on how to add the retrieved OAuth 2.0 data to the
 	 * request on the API engine. (People can supply their own.)
 	 */
 	'oauth_20_redirect_uri'	=> 'https://example.com/oauth20',
 	
 );
 
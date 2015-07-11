<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Error messages for customers - not version specific
 */

return array(
	
	// Status code based messages
	'default'	=> 'Oops it broke. We\'re sorry, but your call cannot be completed as entered. Please check your data or try your call again later.',
    '301'		=> 'Moved. Remember the trailing slash at the end of the URL. Redirects are disabled for your safety.',
    '302'		=> 'Moved. Remember the trailing slash at the end of the URL. Redirects are disabled for your safety.',
    '400'		=> 'Bad Request',
    '401'		=> 'Unauthorized',
    '402'		=> 'Payment Required',
    '403'		=> 'Forbidden',
    '404'		=> 'Not Found',
    '405'		=> 'Method Not Allowed',
	'406'		=> 'Not Acceptable',
    '409'		=> 'Conflict',
    '410'		=> 'Gone',
    '413'		=> 'Request Entity Too Large',
    '418'		=> 'I\'m a Teapot',
    '429'		=> 'Too Many Requests',
    '500'		=> 'Internal Server Error',
    '501'		=> 'Not Implemented',
    '502'		=> 'Bad Gateway',
    '503'		=> 'Service Unavailable',
    '504'		=> 'Gateway Timeout',
    '505'		=> 'HTTP Version Not Supported',
    '506'		=> 'Variant Also Negotiates',
	
	// System-wide messages
    'no_request'	=> 'Please visit bitapihub.com to learn how you may use this API.',
    'bad_version'	=> 'You must specify a valid API version you wish to use.',
	
);

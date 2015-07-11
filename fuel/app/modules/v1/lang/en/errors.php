<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Error messages for customers for V1 of the API engine
 */

return array(
	
	'im_a_teapot'	=> 'There is coffee all over the world. Increasingly, in a world in which computing
						is ubiquitous, the computists want to make coffee. Coffee brewing is an art, but
						the distributed intelligence of the web-connected world transcends art.  Thus,
						there is a strong, dark, rich requirement for a protocol designed espressoly for
						the brewing of coffee. Coffee is brewed using coffee pots.  Networked coffee pots
						require a control protocol if they are to be controlled.

						Increasingly, home and consumer devices are being connected to the Internet. Early
						networking experiments demonstrated vending devices connected to the Internet for
						status monitoring [COKE]. One of the first remotely _operated_ machine to be hooked
						up to the Internet, the Internet Toaster, (controlled via SNMP) was debuted in 1990
						[RFC2235].

						The demand for ubiquitous appliance connectivity that is causing the consumption of
						the IPv4 address space. Consumers want remote control of devices such as coffee pots
						so that they may wake up to freshly brewed coffee, or cause coffee to be prepared at
						a precise time after the completion of dinner preparations.

						Source: https://tools.ietf.org/html/rfc2324',
	
	'maxed_out_limits'	=> 'Congratulations. You\'ve maxed out your API calls. Please purchase additional API calls, or upgrade your package to immediately restore access.',
	'bad_body'			=> 'The body of your request was in an improper format or it does not exist.',
	'not_public'		=> 'This API call may not be made through public interfaces. Please call it server-side.',
	'invalid_api_name'	=> 'The API you have called does not exist. Please hang up, and try a different call.',
	'disabled_api'		=> 'The requested API is disabled.',
	'upgrade_required'	=> 'The requested API or Data Call is unavailable on your current package.',
	'bad_param'			=> '":required_param" is missing or invalid in configure.:location. See the documentation for your desired API to verify the paramater type, and other validation requirements.',
	'too_many_requests'	=> 'Woah! Slow down, champ. You\'ve exceeded the speed limit of :allotment requests per :period.',
	'bad_static'		=> 'Static is bad. Don\'t do drugs. Instead, change your static-call to a valid call.',
	'no_static'			=> 'That API doesn\'t have any configured static calls.',
	'disabled_static'	=> 'The static API call requested is disabled',
	'missing_configure'	=> 'Your "configure" array is missing or invalid. If you\'re making a static call, use "static-call."',
	'missing_auth'		=> 'Your credentials for the third-party API are required and could not be located.',
	'no_credentials'	=> 'The server couldn\'t locate the credentials needed for your call. If you\'re making a static call, contact the API provider.',
	'new_line_header'	=> 'Headers may not contain new line characters.',
	'invalid_body_type'	=> 'The API definition doesn\'t contain a processable body format.',
	'schema_val_fail'	=> 'Your call\'s body contents failed schema validation. See the documentation for the required structure or contact your API provider for assistance.',
	'invalid_schema_type'	=> 'An invalid schema exists for the API. We cannot validate your request.',
	'bad_domain'		=> 'A man walked into someone who walked into the man who walked into someone who... Well, you get the picture. We can\'t always call ourself to say hello, and trying to call our API from our API is one of those occasions. Oh, and sometimes API providers misbehave... so we block them. It could also be that.',
	'cred_not_string'	=> 'The posted credentials are in the wrong format.',
	'remote_unavailable'	=> 'The remote API server didn\'t feel like chatting today.',
	'bad_response'		=> 'The remote server started speaking gibberish, so I hung up on him. I don\'t know what he said. Contact the API provider to switch API response formats so we can communicate.',
	'bad_dynamic'		=> 'Your dynamic call was a bit lacking in the "configure" department. Make sure the call exists and you have a "uri" param, and HTTP method verb ("method") set. See the documentation for more information.',
	'bad_protocol'		=> 'Your configure.url parameter must specify a processable protocol. Your protocol must be one of the follow: :protocols',
	'bad_format'		=> 'We cannot convert your body to the specified format. If you wish to be slimmer, press 1, more muscular, press 2, or to make this error go away, pass your manually converted body as a string in configure.body.',
	'bad_format_static'	=> 'We cannot convert your body to the specified format. If you wish to be slimmer, press 1, more muscular, press 2, or to make this error go away, contact your API Provider.',
	'bad_security'		=> 'The security methods on the API call weren\'t usable, possibly due to missing credentials. Therefore, we cannot process your call.',
	'no_url'			=> 'Your custom call requires you to set configure.url.',
	'bad_data_call'		=> 'The requested Data Call does not exist.',
	'disabled_data_call'	=> 'The Data Call you\'ve requested is currently disabled.',
	'data_call_misconfig'	=> 'The Data Call requested is misconfigured, and therefore unusable.',
	'no_js_calls'		=> 'You may not make Data Calls or dynamic calls through the JS API.',
	'oauth1_authorize'	=> ':url',
	'oauth2_authorize'	=> ':url',
	
);

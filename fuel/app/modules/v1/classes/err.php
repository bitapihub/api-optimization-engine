<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Error codes
 */

namespace V1;

class Err
{
	/**
	 * General
	 */
	const NOT_PUBLIC = 'not public';
	
	/**
	 * Easter Egg
	 */
	const IM_A_TEAPOT = 'teapot';
	
	/**
	 * Validate Request
	 */
	const MAXED_OUT_LIMITS	= 'global call limit reached';
	const BAD_BODY			= 'bad body'; // Also used in APICall
	
	/**
	 * Validate API Call
	 */
	const INVALID_API_NAME	= 'invalid api name';
	const DISABLED_API		= 'api inactive';
	const UPGRADE_REQUIRED	= 'upgrade required';
	const TOO_MANY_REQUESTS	= 'too many requests';
	
	/**
	 * Configure API Call
	 */
	const BAD_STATIC		= 'static is bad';
	const NO_STATIC			= 'no static';
	const DISABLED_STATIC	= 'disabled static';
	const MISSING_CONFIGURE	= 'missing configure';
	const MISSING_AUTH		= 'missing auth';
	
	/**
	 * Dynamic Call
	 */
	const BAD_DYNAMIC		= 'bad dynamic';
	const NO_URL			= 'no url';
	
	/**
	 * RAML
	 */
	const INVALID_SCHEMA_TYPE	= 'invalid schema type';
	
	/**
	 * APICall
	 */
	const NO_CREDENTIALS	= 'no credentials';
	const CRED_NOT_STRING	= 'credential not string';
	const BAD_PARAM			= 'bad param';
	const NEW_LINE_HEADER	= 'new line in header';
	const INVALID_BODY_TYPE	= 'invalid body type';
	const SCHEMA_VAL_FAIL	= 'schema validation failed';
	const BAD_DOMAIN		= 'bad domain';
	const BAD_PROTOCOL		= 'bad protocol';
	const BAD_FORMAT		= 'bad format';
	const BAD_SECURITY		= 'bad security';
	
	/**
	 * RUN CALL
	 */
	const BAD_RESPONSE		= 'bad response';
	
	/**
	 * Data Call
	 */
	const BAD_DATA_CALL			= 'bad data call';
	const DISABLED_DATA_CALL	= 'disabled_data_call';
	const DATA_CALL_MISCONFIG	= 'data call misconfigured';
	const NO_JS_CALLS			= 'no js calls';
	
	/**
	 * Security Calls
	 */
	const OAUTH1_AUTHORIZE		= 'must oauth1 authorize';
	const OAUTH2_AUTHORIZE		= 'must oauth2 authorize';
}

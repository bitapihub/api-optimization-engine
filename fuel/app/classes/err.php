<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Non-version-specific error codes
 */

class Err
{
	/**
	 * Generic errors
	 */
	const UNKNOWN_ERROR	= 'unknown error';
	
	const REDIRECT		= 'redirect';
	const CLIENT_ERROR	= 'client error';
	const SERVER_ERROR	= 'server error';
	
	/**
	 * Loader errors
	 */
	
	const BAD_OR_NO_VERSION = 'bad version';
}

<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * PHPUnit Tests
 */

namespace Test;

/**
 * Tests for class \Log
 * 
 * @group App
 * @group Log
 */
class Log extends \TestCase
{
	public function test_logger()
	{
		$tokens = array(
			
			'test token 1'							=> 'value',
			'testtoken2'							=> 'value',
			'`~!@#$%^&*()_+-=[]{}\\|;:\'",<.>/?'	=> 'value',
			
		);
		
		\Log::logger('INFO', 'PHPUNIT:LOGGER', 'The PHPUnit test for \Log::logger() was successful.', __METHOD__, $tokens);
		
		// If we don't get errors, then we're good to go.
	}
}

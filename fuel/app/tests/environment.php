<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * PHPUnit Tests
 */

namespace Test;

/**
 * Environment class tests
 * 
 * @group App
 * @group Environment
 */
class Environment extends \TestCase
{
	public function test_set_language()
	{
		// Set the default to English
		\Config::set('language', 'en');
		
		// Set it to eSpanish :)
		\Environment::set_language('es');
		$this->assertSame('es', \Config::get('language', false));
		
		// The next line of code shouldn't do anything to the value.
		\Environment::set_language();
		$this->assertSame('es', \Config::get('language', false));
	}
}

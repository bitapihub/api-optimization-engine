<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * PHPUnit test for the Account object
 */

namespace Test;

/**
 * \Account class tests
 *
 * @group App
 * @group Account
 */
class Account extends \TestCase
{
	public function __construct()
	{
		\Module::load('v1');
	}
	
	public function test_valid_timestamp()
	{
		\Config::load('engine', 'engine');
		$timestamp_cutoff = \Config::get('engine.timestamp_cutoff', 15);
		
		$time = \Date::forge()->get_timestamp()-1;
		$outdated = $time - $timestamp_cutoff;
		
		$this->assertSame(true, \V1\Account::valid_timestamp($time));
		$this->assertSame(false, \V1\Account::valid_timestamp($outdated));
	}
	
	public function test_valid_nonce()
	{
		$tokens = array(
			
			'oauth_consumer_key'	=> 'test-key',
			'oauth_nonce'			=> 'test-nonce',
			'oauth_timestamp'		=> \Date::forge()->get_timestamp(),
			
		);
		
		$this->assertSame(true, \V1\Account::valid_nonce($tokens));
		$this->assertSame(false, \V1\Account::valid_nonce($tokens));
	}
}

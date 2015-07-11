<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * PHPUnit Tests
 */

namespace Test;

/**
 * Unit tests for class \Utility
 * 
 * @group App
 * @group Utility
 */
class Utility extends \TestCase
{
	public function test_set_cache()
	{
		// Save the environment. While we're at it, save the whales, too.
		$backup = \Fuel::$env;
		
		/*******************
		 * TEST A
		 */
		
		// Configure the environment to something that shouldn't allow for caching.
		\Fuel::$env = 'private';
		
		\Utility::set_cache('test_set_cache', 'testvalue');
		
		try {
			
			\Cache::get('test_set_cache');
			\Fuel::$env = $backup;
			
			// Delete it for subsequent nuns.
			\Cache::delete('test_set_cache');
			
			$this->fail;
			
		} catch (\CacheNotFoundException $e) {
			//The private and development environments should not cache the requests using \Utility.
		}
		
		/*******************
		 * TEST B
		 */
		
		// Set the environment to allow for caching.
		\Fuel::$env = 'test';
		
		\Utility::set_cache('test_set_cache', 'testvalue');
		
		try {
				
			\Cache::get('test_set_cache');
				
		} catch (\CacheNotFoundException $e) {
				
			//The private and development environments should not cache the requests using \Utility.
			\Fuel::$env = $backup;
			$this->fail;
				
		}

		// Delete it for subsequent nuns.
		\Cache::delete('test_set_cache');
		
		// Restore the environment. (Eden?)
		\Fuel::$env = $backup;
	}
	
	public function test_generate_random_id()
	{
		$test_subject = \Utility::generate_random_id();
		
		$this->assertInternalType('string', $test_subject);
		$this->assertSame(36, strlen($test_subject));
		$this->assertSame(5, count(explode('-', $test_subject)));
	}
	
	public function test_unique_user()
	{
		$test_subject_a = \Utility::unique_user();
		$test_subject_b = \Utility::unique_user();
		
		$this->assertInternalType('string', $test_subject_a);
		$this->assertSame(36, strlen($test_subject_a));
		$this->assertSame(5, count(explode('-', $test_subject_a)));
		
		$this->assertSame($test_subject_a, $test_subject_b);
	}
	
	public function test_format_error()
	{
		$custom_response = array(
			
			'errors'	=> array(
			
				'code'		=> 500,
				'type'		=> 'server error',
				'message'	=> 'Test server error',
				 
			),
			
		);
		
		$default_response = array(
			
			'errors'	=> array(
			
				'code'		=> 500,
				'type'		=> 'server error',
				'message'	=> 'Internal Server Error',
				 
			),
			
		);
		
		// Custom
		$this->assertSame($custom_response, \Utility::format_error(500, \Err::SERVER_ERROR, 'Test server error'));
		
		// Default
		$this->assertSame($default_response, \Utility::format_error(500));
	}
	
	public function test_format_response()
	{
		$time = time();
		
		$expected = array(
			 
			'code'		=> 200,
			'ts'		=> $time,
			'response'	=> array(
				
				'test'	=> 'test value',
				
			),
			 
		);
		
		$this->assertSame($expected, \Utility::format_response(200, array('test' => 'test value'), $time));
	}
	
	public function test_subtract_seconds()
	{
		$this->assertInternalType('integer', \Utility::subtract_seconds('year'));
	}
	
	public function test_prepare_response()
	{
		$response_array = array(
			
			'node'	=> array(
				
				'test1'	=> 'value 1',
				'test2'	=> 'value 2',
				
			),
			
		);
		
		// JSON
		$response_string = \Utility::prepare_response($response_array);
		
		$this->assertSame('{"node":{"test1":"value 1","test2":"value 2"}}', $response_string);
		
		// XML
		\Session::set('response_format', 'xml');
		$response_string = \Utility::prepare_response($response_array);
		
		$this->assertSame('<?xml version="1.0" encoding="utf-8"?>
<xml><node><test1>value 1</test1><test2>value 2</test2></node></xml>
', $response_string);
		
		// Clean
		\Session::delete('response_format');
	}
	
	public function test_get_format()
	{
		$json_formats = array(
 			
 			'application/json',
 			'text/json',
 		
 		);
		
		$json = \Utility::get_format('application/json');
		$app_json = \Utility::get_format('json');
		
		$this->assertSame('json', $json);
		$this->assertSame($json_formats, $app_json);
	}
	
	public function test_is_serialized()
	{
		$this->assertSame(true, \Utility::is_serialized(serialize(true)));
		$this->assertSame(false, \Utility::is_serialized('not serialized'));
	}
	
	public function test_is_internal_call()
	{
		$this->assertInternalType('boolean', \Utility::is_internal_call());
	}
	
	public function test_get_nonce()
	{
		$nonce = strlen(\Utility::get_nonce());
		$this->assertSame(64, $nonce);
	}
}

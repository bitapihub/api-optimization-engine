<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * PHPUnit tests for \APIRequest
 */

namespace Test;

/**
 * PHPUnit tests for \APIRequest
 * 
 * @group App
 * @group APIRequest
 */
class APIRequest extends \TestCase
{
	public $call_data = array();
	
	public function __construct()
	{
		$this->call_data = array(
					
			'data-call'	=> 'my-call',
			'test1'		=> 'val1',
			'test2'		=> 'val2'
	
		);
	}
	
	public function test_setup()
	{
		$bad_call = array(serialize('test'));
		
		// Use a Data Call so we can specify the post data.
		\Session::set('data_call', true);
		
		// Bad call setup
		\Session::set('posted_data', $bad_call);
		$this->assertInternalType('array', \V1\APIRequest::setup());
		
		// Correct call setup
		\Session::set('posted_data', $this->call_data);
		$this->assertSame(true, \V1\APIRequest::setup());
	}
	
	public function test_is_static()
	{
		$this->assertSame(false, \V1\APIRequest::is_static());
	}
	
	public function test_post_data()
	{
		$this->assertSame($this->call_data, \V1\APIRequest::post_data());
	}
	
	public function test_get()
	{
		$this->assertSame('val1', \V1\APIRequest::get('test1'));
		$this->assertSame(false, \V1\APIRequest::get('fake', false));
	}
	
	public function test_set()
	{
		\V1\APIRequest::set('test4', 'find me');
		$this->assertSame('find me', \V1\APIRequest::get('test4'));
	}
	
	public function test_data_call()
	{
		$this->assertSame('my-call', \V1\APIRequest::data_call('data-call'));
		$this->assertSame(false, \V1\APIRequest::data_call('fake', false));
	}
}

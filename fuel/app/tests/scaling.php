<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * PHPUnit Tests
 */

namespace Test;

/**
 * Unit tests for class \Scaling
 * 
 * @group App
 * @group Scaling
 */
class Scaling extends \TestCase
{
	/**
	 * Must be before test_remove_lock()
	 */
	public function test_set_lock()
	{
		$test_subject_a = \Scaling::set_lock('test_set_lock', 5);
		$test_subject_b = \Scaling::set_lock('test_set_lock', 5);
		
		$this->assertSame(true, $test_subject_a);
		$this->assertSame(false, $test_subject_b);
	}
	
	/**
	 * Must be after test_set_lock()
	 */
	public function test_remove_lock()
	{
		$test_subject = \Scaling::remove_lock('test_set_lock');
		
		// If there isn't an error, then it worked.
	}
}

<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * PHPUnit Tests
 */

namespace Test;

/**
 * Unit tests for class \Remote
 * 
 * @group App
 * @group Remote
 */
class Remote extends \TestCase
{
	public function test_forge()
	{
		$test_subject = \Remote::forge('http://google.com', 'curl', 'get');
		
		$this->assertInternalType('object', $test_subject);
		$this->assertSame('GET', $test_subject->get_method());
	}
}

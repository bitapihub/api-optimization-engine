<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * PHP Unit Tests
 */

namespace Test\Monolog\Processor;

/**
 * Monolog\Processor\BitAPIHubProcessor class tests
 * 
 * @group App
 * @group Monolog
 */
class BitAPIHubProcessor extends \TestCase
{
	public function invoke_provider()
	{
		return array(
			
			array(
				array(
					'message'	=> 'Test message',
					'extra'		=> array('blah'),
				),
				false,
			),
			
			array(
				array(
					'message'	=> 'Test message[-HACKISH-]'.json_encode(array('foo' => 'bar')),
					'extra'		=> array('foo'),
				),
				'array'
			),
			
			array(
				array(
					'message'	=> 'Test message[-HACKISH-]I\'m Doomed',
					'extra'		=> array('foo'),
				),
				'null',
			),
			
		);
	}
	
	/**
	 * @dataProvider invoke_provider
	 */
	public function test_invoke(array $record, $expected_context)
	{
		$processor		= new \Monolog\Processor\BitAPIHubProcessor;
		$test_subject	= $processor($record);

		$this->assertSame('Test message', $test_subject['message']);
		
		if (is_bool($expected_context)) {
			$this->assertSame(false, array_key_exists('context', $test_subject));
		} else {
			$this->assertInternalType($expected_context, $test_subject['context']);
		}
		
		if (isset($test_subject['extra']['user_agent'])) {
			$this->assertInternalType('string', $test_subject['extra']['user_agent']);
		}
		
		$this->assertInternalType('int', $test_subject['extra']['timestamp']);
		$this->assertInternalType('string', $test_subject['extra']['unique_user']);
	}
}

<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * PHP Unit Tests
 */

namespace Test\Monolog\Formatter;

/**
 * Monolog\Formatter\CustomJsonFormatter class tests
 * 
 * @group App
 * @group Monolog
 */
class CustomJsonFormatter extends \TestCase
{
	public function test_format()
	{
		$formater = new \Monolog\Formatter\CustomJsonFormatter;
		
		$this->assertSame(
			json_encode(array('foo' => 'bar')).PHP_EOL,
			$formater->format(array('foo' => 'bar'))
		);
	}
	
	public function test_formatBatch()
	{
		$formater = new \Monolog\Formatter\CustomJsonFormatter;
		
		$this->assertSame(
			json_encode(array('foo' => 'bar')).PHP_EOL,
			$formater->formatBatch(array('foo' => 'bar'))
		);
	}
}

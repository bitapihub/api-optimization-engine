<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * PHPUnit tests for \V1\Raml
 */

namespace Test;

/**
 * PHPUnit tests for \V1\Raml
 * 
 * @group App
 * @group Raml
 */
class Raml extends \TestCase
{
	public $raml = <<<'RAML'
#%RAML 0.8
title: ZEncoder API
version: v2
baseUri: https://app.zencoder.com/api/{version}
limits:
  level1:
    day: 50
    month: 1000
  level2:
    minute: 5
    month: 250000
/{{static-calls}}:
  /callname:
    get:
      queryParams:
        test:
          type: integer
/jobs:
  post:
    description: Create a Job
    headers:
      Zencoder-Api-Key:
        displayName: ZEncoder API Key
    responses:
      503:
        headers:
          X-waiting-period:
            description: |
              The number of seconds to wait before you can attempt to make a request again.
            type: integer
            required: yes
            minimum: 1
            maximum: 3600
            example: 34
RAML;
	
	public function test_parse()
	{
		$this->assertInstanceOf('\Raml\APIDefinition', \V1\RAML::parse($this->raml));
	}
	
	public function test_parse_static_calls()
	{
		$this->assertInternalType('array', \V1\Raml::parse_static_calls($this->raml));
	}
	
	public function test_parse_package_limits()
	{
		$this->assertInternalType('array', \V1\Raml::parse_package_limits($this->raml));
	}
}

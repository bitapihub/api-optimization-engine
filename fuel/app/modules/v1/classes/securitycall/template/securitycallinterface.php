<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Definition interface for security call objects
 */

namespace V1\SecurityCall\Template;

interface SecurityCallInterface
{
	/**
	 * Run the request
	 * 
	 * @param \Raml\SecurityScheme $securityscheme_obj The security scheme to process the call data for
	 * @param \V1\APICall $apicall_obj					The APICall object
	 * 
	 * @return mixed The object we just completed or an array describing the next step in the security process
	 */
	public function run(\Raml\SecurityScheme $securityscheme_obj, \V1\APICall $apicall_obj);
}

<?php
/**
 * Copyright 2015 Bit API Hub
 * 
 * Configuration file for account tiers
 */

return array(
	
	/**
	 * Remember:
	 * 1. The higher the access level, the more goodies people have access to.
	 * 2. max_calls when set to 0 means the tier, by default, isn't limited by how many calls they may make.
	 */
	
	'free'	=> array(
	
		'name'			=> 'Free for Life',
		'access_level'	=> 1,
		'max_calls'		=> 10000,
		'reset_period'	=> 30, // How many days until we reset their usage stats?
	
	),
	'tier1'	=> array(
	
		'name'			=> 'Tier 1',
		'access_level'	=> 2,
		'max_calls'		=> 1000000,
	
	),
	'tier2'	=> array(
	
		'name'			=> 'Tier 2',
		'access_level'	=> 3,
		'max_calls'		=> 0,
	
	),
	
);

<?php

/**
 *  Copyright 2015 Bit API Hub
 *  
 *  @brief Custom data processor for Bit API Hub
 */

namespace Monolog\Processor;

class BitAPIHubProcessor
{
    /**
     * Add lots of junk just to do it. (Nah, it's useful to maintain system security and
     * trace how people use the system.)
     * 
     * Note: The modified \Log class uses [-HACKISH-] to work around the fact that FuelPHP doesn't support
     * Monolog very well, and it doesn't support "context" for passing around useful environment data.
     * We need those for security, and portability.
     * 
     * @param  array $record The log data collected through Monolog
     * @return array The same array of data with the new data added. 
     */
    public function __invoke(array $record)
    {
    	// Format the message, and place the other data in its proper place if it's hackish.
    	if (strpos($record['message'], '[-HACKISH-]') !== false) {
    		
    		$message_parts = explode('[-HACKISH-]', $record['message']);
    		$record['message'] = $message_parts[0];
    		$record['context'] = json_decode($message_parts[1], true);
    		
    	}
    	
    	// Add extra data
        $record['extra'] = array_merge(
            $record['extra'],
            array(
                'user_agent'	=> isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
            	'timestamp'		=> time(),
        		'unique_user'	=> \Utility::unique_user(),
            )
        );
        
        return $record;
    }
}

<?php
/**
 *  Copyright 2014 Bit API Hub
 *  
 * 	Standardized Bit API Hub application logging interface (PSR-3 compliant levels)
 */

class Log extends \Fuel\Core\Log
{
	/**
	 * Initialize the class
	 */
	public static function _init()
	{
		// Make sure that the file system structure stays intact. Also, create the $monolog instance.
		parent::_init();
	
		// create the streamhandler, and activate the handler
		$stream = static::$monolog->popHandler();
		$stream->setFormatter(new \Monolog\Formatter\CustomJsonFormatter());
		static::$monolog->pushHandler($stream);
		
		// Processors
		static::$monolog->pushProcessor(new \Monolog\Processor\MemoryUsageProcessor());
		static::$monolog->pushProcessor(new \Monolog\Processor\ProcessIdProcessor());
		static::$monolog->pushProcessor(new \Monolog\Processor\WebProcessor());
		static::$monolog->pushProcessor(new \Monolog\Processor\BitAPIHubProcessor());
	}
	
    /**
     * Logs with an arbitrary level.
     * 
     * LEVELS:
     * EMERGENCY	- System is unusable.
     * 
     * ALERT		- Action must be taken immediately. (Example: Entire website down, database unavailable, etc. This should
     * 					trigger the SMS alerts and wake you up.)
     * 
     * CRITICAL		- Critical conditions. (Example: Application component unavailable, unexpected exception.)
     * 
     * ERROR		- Runtime errors that do not require immediate action but should typically be logged and monitored.
     * 
     * WARNING		- Exceptional occurrences that are not errors. (Example: Use of deprecated APIs, poor use of an
     * 					API, undesirable things that are not necessarily wrong.)
     * 
     * NOTICE		- Normal but significant events.
     * 
     * INFO			- Interesting events. (Example: User logs in, SQL logs.)
     * 
     * DEBUG		- Detailed debug information.
     *
     * @param mixed $level		The level to log at (A number or string)
     * @param string $action	The action that took place
     * @param string $msg		The logged message
     * @param string $method	The method that sent the message
     * @param array $tokens		An array of data with points of interest
     */
    public static function logger($level, $action, $msg, $method = null, array $tokens = array())
    {
    	\logger(strtoupper($level), static::_format($action, $msg, $tokens), $method);
    }
    
    /**
     * Orm notifier method for logging DB create, update, and delete queries 
     * 
     * @param Orm\Model $model	The model performing the query
     * @param string $event		The event name from
     * 							@link http://fuelphp.com/docs/packages/orm/observers/creating.html#/event_names
     */
    public static function orm_notify(Orm\Model $model, $event)
    {
    	/*
    	 * Grab before and after data (get_data() must be on every model that creates, updates, and deletes,
    	 * so this code is intended to fail when it isn't present.)
    	 */
    	$tokens = array();
    	switch ($event) {
    		
    		case 'after_insert':
    			$tokens = $model->get_data();
    			$message = 'Added a row through model '.get_class($model);
    			break;
    		
    		case 'after_update':
    			$tokens = $model->get_data();
    			$message = 'Updated a row through model '.get_class($model);
    			break;
    		
    		case 'after_delete':
    			$tokens = $model->get_data();
    			$message = 'Deleted a row through model '.get_class($model);
    			break;
    		
    	}
    	
    	// If we're tracking the event in progress, log it.
    	if (!empty($tokens)) {
    		static::logger('INFO', 'SQL:'.$event, $message, __METHOD__, $tokens);
    	}
    }
    
    /*
     * PRIVATE PARTS
     */
    
    /**
     * Set the debug_backtrace if we're in debug mode.
     * 
     * @param string $action	The action that took place
     * @param string $msg		The logged message
     * @param array $tokens		An array of data with points of interest
     * 
     * @return string The logged message possibly with the backtrace appended
     * @access private
     */
    private static function _format($action, $msg, array $tokens)
    {
    	// Fix the formatting
    	$msg = str_replace(array("\t", "\n", "\r", "  "), array('', ' ', ' ', ' '), $msg);
    	
    	$output = array(
    		
    		'action'	=> $action,
    		'real_ip'	=> \Input::real_ip(),
    		'message'	=> $msg,
    		'tokens'	=> $tokens,
    		
    	);
    	
    	// If we have a sub-action then add that in.
    	if (substr_count($action, ':') === 1) {
    		
    		$action_arr = explode(':', $action);
    		
    		$output['action'] 		= $action_arr[0];
    		$output['subaction']	= $action_arr[1];
    		
    	}
    	
    	return '[-HACKISH-]'.json_encode($output);
    }
}

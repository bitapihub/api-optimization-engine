<?php
/**
 *  Copyright 2014 Bit API Hub
 *  
 *  The template wrapper for modules to use
 */

namespace Controller;

class Loader extends \Controller_Rest
{
    /**
     *  Run this before every call
     *  
     *  @return void
     *  @access public
     */
    public function before()
    {
    	// Profile the loader
    	\Profiler::mark('Start of loader\'s before() function');
    	\Profiler::mark_memory($this, 'Start of loader\'s before() function');
    	
        // Set the environment
        parent::before();
        
        // Load the config for Segment so we can process analytics data.
        \Config::load('segment', true);
        
        // Load the config file for event names. Having events names in one place keeps things synchronized.
        \Config::load('analyticsstrings', true);
        
        // Engine configuration
        \Config::load('engine', true);
		
		// Load the package configuration file.
		\Config::load('tiers', true);
		
		// Soccket connection configuration
		\Config::load('socket', true);
        
        /**
         * Ensure that all user language strings are appropriately translated.
         * 
         * @link https://github.com/fuel/core/issues/1860#issuecomment-92022320
         */
        if (is_string(\Input::post('language', false))) {
        	\Environment::set_language(\Input::post('language', 'en'));
        }
        
        // Load the error strings.
        \Lang::load('errors', true);
    }
    
    /**
     *  Load every call to the API with this method.
     *  
     *  @return void
     *  @access public
     */
    public function action_index()
    {
    	// Profile the loader
    	\Profiler::mark('Start of loader\'s action_index() function');
    	\Profiler::mark_memory($this, 'Start of loader\'s action_index() function');
    	
    	// Make sure we aren't processing crap.
    	if (in_array($this->format, array('csv', 'php', 'serialize'))) {
    		$this->format = 'json';
    	}
    	
    	// For some reason this value is quoted when set to html.
    	if (\Input::post('format') === '"html"') {
    		$this->format = 'html';
    	}
    	
    	// Cleanse the session to keep things stable.
    	\Session::destroy();
    	
    	// For error handling
    	\Session::set('response_format', $this->format);
    	
    	// External error processing through Apache
    	if (\Uri::segment(1) === 'error' && is_numeric(\Uri::segment(2)) && strlen(\Uri::segment(2)) === 3) {
    		return $this->response(\Utility::format_error(\Uri::segment(2)));
    	}
    	
    	// /loader/index/error/404 style (Due to routing)
    	if (
    		substr_count(\Uri::current(), 'loader/index/error') === 1 &&
    		is_numeric(\Uri::segment(4)) &&
    		strlen(\Uri::segment(4)) === 3
    	) {
    		return $this->response(\Utility::format_error(\Uri::segment(4)));
    	}
    	
    	// We need a version number
    	if (empty(\Uri::segment(1)) || \Module::exists(\Uri::segment(1)) === false) {
    		
    		$error_data = \Utility::format_error(400, \Err::BAD_OR_NO_VERSION, \Lang::get('errors.bad_version'));
    		return $this->response($error_data, 400);
    		
    	}
    	
        // We need a request.
        if (empty(\Input::post()) || \Input::method() !== 'POST') {
        	
            $error_data = \Utility::format_error(405, null, \Lang::get('errors.no_request'));
            return $this->response($error_data, 405);
            
        }
        
        // Pass the request to the proper API version request handler. (Module)
        if (!empty(\Input::post())) {
        	
        	\Module::load(\Uri::segment(1));
        	$response = \Request::forge(\Uri::segment(1).'/index', false)->execute()->response->body;
        	
        	// HTML only Data Calls
        	if (is_string($response)) {
        		return $this->response($response, 200);
        	}
        	
            return $this->response($response[0], $response[1]);
        	
        }
    }
}

<?php
/**
 *  Copyright 2014 Bit API Hub
 *  
 *  Set various environment settings
 */

class Environment
{
    /**
     *  Figure out which language to use for the site
     *  
     *  @param mixed $force    Set this to boolean false to let the system guess the language, or set this to a language to use.
     */
    public static function set_language($force = false)
    {
        // Are we deliberately setting the language?
        if (is_string($force)) {
        
            \Config::set('language', $force);
            return;
        
        }
        
        /**
         * @TODO Later we should check if the HTTP header requested language exists, and use it if we have
         * language files for it. For now we skip that since we're only using one language, English.
         * @see Self::_get_requested_langs()
         */
    }
    
    /**
     * PRIVATE PARTS
     */
    
    /**
     * \Agent has lost his mind. I mean he has memory issues... so we try to stay away from him.
     * (browscap.ini is HUGE and when it tries to compile through PHP, it messes up and exceeds PHP's
     * memory limits. It's far too hackish when PHP can't load browscap, and it just spits out lots of
     * bugs, so we don't use it.)
     *  
     * @return array The array of possible languages, or an empty array
     */
    private static function _get_requested_langs()
    {
    	// Get the languages
    	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    		
    		$http_langs = explode(';', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    		return explode(',', $http_langs[0]);
    		
    	}
    	
    	return array();
    }
}

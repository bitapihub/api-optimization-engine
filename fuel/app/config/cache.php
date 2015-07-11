<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2014 Fuel Development Team
 * @link       http://fuelphp.com
 */

/**
 * NOTICE:
 *
 * If you need to make modifications to the default configuration, copy
 * this file to your app/config folder, and make them in there.
 *
 * This will allow you to upgrade fuel without losing your custom config.
 */


return array(

    /**
     * ----------------------------------------------------------------------
     * global settings
     * ----------------------------------------------------------------------
     */

    // default storage driver
    'driver'      => 'redis',

    // default expiration (null = no expiration)
    'expiration'  => null,

    /**
     * Default content handlers: convert values to strings to be stored
     * You can set them per primitive type or object class like this:
     *   - 'string_handler'         => 'string'
     *   - 'array_handler'            => 'json'
     *   - 'Some_Object_handler'    => 'serialize'
     */

    /**
     * ----------------------------------------------------------------------
     * storage driver settings
     * ----------------------------------------------------------------------
     */

    // specific configuration settings for the redis driver
    // 'redis'  => array(
    //     'database'  => 'default'  // name of the redis database to use (as configured in config/db.php)
    // ),
    
);



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

    'active' => 'default',
    
    'default' => array(
        'type'           => 'pdo',
        'connection'     => array(
            'dsn'        => 'mysql:host=localhost;dbname=api',
            'username'   => 'root',
            'password'   => 'password',
            'persistent'     => false,
            'compress'       => true,
        ),
        'identifier'     => '`',
        'table_prefix'   => 'hgd3_',
        'charset'        => 'utf8',
        'enable_cache'   => true,
        'profiling'      => true,
        'readonly'       => false,
    ),

    /**
     * Base Redis config
     */
    'redis' => array(
        'default' => array(
            'hostname'  => '127.0.0.1',
            'port'      => 6379,
            'timeout'    => null,
            'database'  => 0,
            'password'  => 'password'
        )
    ),

);

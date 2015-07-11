<?php
// Bootstrap the framework DO NOT edit this
require COREPATH.'bootstrap.php';


Autoloader::add_classes(array(
    // Add classes you want to override here
    'Presenter'					=> APPPATH.'classes/presenter.php',
    'Controller'				=> APPPATH.'classes/controller.php',
    'Log'						=> APPPATH.'classes/log.php',
    'HttpNotFoundException'		=> APPPATH.'classes/httpexceptions.php',
    'HttpServerErrorException'	=> APPPATH.'classes/httpexceptions.php',
    'HttpBadRequestException'	=> APPPATH.'classes/httpexceptions.php',
));

// Register the autoloader
Autoloader::register();

/**
 * Your environment.  Can be set to any of the following:
 *
 * Fuel::DEVELOPMENT
 * Fuel::TEST
 * Fuel::STAGING
 * Fuel::PRODUCTION
 */
Fuel::$env = (isset($_SERVER['FUEL_ENV']) ? $_SERVER['FUEL_ENV'] : 'private');

// Initialize the framework with the config file.
Fuel::init('config.php');

// Make the debugger show all tree items expanded and up to the specified recursion level.
Debug::$js_toggle_open = true;
Debug::$max_nesting_level = 10; // Default is 5

// Set the timezone to the default display timezone. Customers may change this later.
\Date::display_timezone('America/New_York');

// Rotate the session.
\Session::rotate();

// Unique user ID for the current page load (Used by \Monolog\Processor\BitAPIHubProcessor)
\Utility::unique_user();

// Set the language
\Environment::set_language();

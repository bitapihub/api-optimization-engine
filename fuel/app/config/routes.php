<?php
return array(
    '_root_'						=> 'loader/index',					// The default route
    '_404_'							=> 'loader/index/error/404',		// The main 404 route
    'api/app'						=> 'api/app',						// Tests fail without this route.
    'api/app/(:any)'				=> 'api/app/$1',					// Tests fail without this route.
    '(:any)'						=> 'loader/index/$1',				// Any other pages
);
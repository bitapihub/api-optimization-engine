API Optimization Engine Manual
------------------------------

**This manual is not yet completed.**

This manual will guide you through the process of setting up your call processing server with the API Optimization
Engine. After you've set up your server, you'll need to install the SDK for the engine. The connection between the
call processing server and your web servers is secured with a stripped-down version of OAuth 1.0a. Some features of
OAuth 1.0a (such as request keys) are unnecessarily processing intensive for the purposes of an internal connection,
so they were removed. In future versions we're considering implementing a true OAuth 1.0a connection to allow for
easier integration with existing solutions.

**There is not currently an administration interface for this software!** In the future we may consider writing a
simple tool to administrate the engine. The interface will probably just be simple FuelPHP tasks for use with the
Oil command line tool. The only administration tool right now is a tool used for encrypting the credentials for the
system APIs, as without the tool there isn't any way to store the credentials for the system APIs.


Contents
--------

1. Installing the API Optimization Engine
2. Integrating the engine with your product
3. Adding your first API
4. Adding the credentials for a system-owned API
5. Adding the credentials for an account-owned API
6. The components of a request

Part 1: Installing the API Optimization Engine
----------------------------------------------

Copy the .htaccess code to Apache's httpd.conf file or edit your webserver's config to match. That will speed up
access to the engine.

Part 2: Integrating the engine with your product
------------------------------------------------

There are samples included with the AOE-PHP-SDK repo.

Part 3: Adding your first API
-----------------------------

Part 4: Adding the credentials for a system-owned API
-----------------------------------------------------

Use an Oil task to encrypt the credentials.

`php oil refine apicredentials /path/to/json/file`

See `php oil refine apicredentials help` for more information.

Part 5: Adding the credentials for an account-owned API
-------------------------------------------------------

Part 6: The components of a request
-----------------------------------

Review the RAML sheet for the engine. Each API version has its own RAML spec sheet included with it for easy reading.
Check out the /fuel/app/modules/raml directory.
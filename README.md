API Optimization Engine
=======================

The API Optimization Engine makes it faster for your server to talk to other servers while simplifying your
software development, and keeping your code tidy. This engine implements the optimizations as defined in
Rick Mac Gillis' book, "[The New Frontier in Web API Programming - Your Guide to API Optimization.](https://static.bitapihub.com/assets/docs/the-new-frontier-in-web-api-programming.pdf)"

The engine supports an account-based environment to cater to the needs of SaaS companies looking to provide
different APIs to different accounts, and different APIs to different account packages. Each account may own
an API, and APIs may also be marked as available to anyone of a minimum package level.

**Note:** This software is designed for companies with large numbers of API calls, either by multiple users
or through multiple calls by one user. It's designed to speed up your company's network by reducing the load of
expensive API calls on your client-facing infrastructure. If you aren't processing a large volume of API calls
(Around 500+ per minute) or your calls are not "heavy" calls (1MB+), then you probably won't benefit from using
this software. In addition, the engine is designed to run in the cloud, not shared hosting or a VPS system. It
should be installed on its own virtual or physical system. 

Requirements
------------

- PHP 5.6+
- MySQL or MariaDB 5.5+

Optional
--------

- [OAuth PECL extension](https://pecl.php.net/package/oauth) if you need OAuth 1.0a support.

Framework
---------

The engine is built using the fastest full-featured PHP Framework, [FuelPHP](http://fuelphp.com). Check out the
configuration settings for the framework for a fully customized system, including your choice of caching systems
and databases.

Project Status
--------------

**Status**: Discontinued

The last state of this project is beta. As I have too many client projects that I'm working on, I'm not currently maintaining this project any longer. If it gains enough traction, I'll consider rewriting it.

**WARNING**
This project is developeed without modular design which is critical to maintainability! Therefore, if you do decide to use this project in whole or in part, you're strongly encouraged to make it modular. When I wrote this, I had not yet discovered Robert C. Martin's [Clean Code](https://cleancoders.com/) standards, and admittedly, the codebase looks like crap. If you're evaluating me for your next project, check out my newer projects, such as [Hack Fast Algos](https://github.com/cozylife/hackfastalgos) or [the source for Rick Mac Gillis.com](https://github.com/cozylife/rickmacgillis).

Limitations
-----------

- The engine doesn't handle file uploads.
- The engine doesn't handle streaming API calls.
- The engine will never threaten you, and in fact, cannot speak.
- The cake is a lie.

Versioning
----------

There are technically two types of versioning for the project.

**Git** - The Git versioning system uses Semantic Versioning ([SemVer](http://semver.org/)) and it ensures that
your code will always remain stable.

**API Version** - The API Version changes when the new API workings are not compatible with the old API workings.
(Such as a syntax change) Think of the API version as an "added feature" while you will still always have the
original version of the API available. Only when the SemVer version changes to a new major release, will any or
all current API versions may be removed or replaced.

Setup
-----

1. In the shell, run the following command where you want to install the engine.
```
composer require bitapihub/api-optimization-engine
```
2. Edit your config files to your liking, especially your DB configuration. Also be sure to change the desired
environment setting in your .htaccess file. Port the .htaccess file to your http.conf to make it run faster.
3. Through the shell interface, change the directory to your root installation directory. (Ex. /var/www/aeo)
then run the following commands:

```
php oil refine install
php oil refine migrate current
```
The second command requires a proper DB configuration.

Your installation is complete, and you can now manually import the demo data from api_data.sql into your database.
Read the [Manual](Manual.md) for further instructions.

Optimizations
-------------

**Call Processing Server** - Route your calls through a single server or a cloud of API call processing servers
to lighten the load on your webserver. Remember that all TCP requests require at least 9 transactions! The API
Optimization engine is the missing code you need to construct a call processing server.

**Batch Procesing** - Batch process your API calls by sending multiple requests to the call processing server
to avoid wasting resources with one-off requests.

**Parallel Call Processing** - The engine uses stream_socket_client() and stream_select() to process your calls
in non-blocking mode (parallel) so you only need to wait as long as it takes for the longest call to complete.
Remember that in serial the call time stacks, making three 5 second calls become a 15 second call.

**[RAML Modeling Language](http://raml.org/spec.html)** - Stop bloating your code with messy SDKs that make your code
harder to read. Simply describe the remote API you're contacting by writing an easy to learn RAML specification.

**Don't Wait for a Response** - You don't need the response from every API you call, so why wait for one? Simply
instruct the engine that you do not wish to receive the response, and it will queue the call and reply with a
generic message of its own.

**Preconfigure Your Requests** - Through the use of the aptly named "static calls," you may preconfigure calls
whos request data remains the same. Therefore, you can maintain the request directly in your RAML document in
the database while only passing basic data to the call processing server.

**Use One Format** - Now you can speak to an XML server in JSON, or a JSON server in XML while keeping your
projects free of conversion classes, and your code much cleaner.

**Caching** - The engine supports caching options through the framework, so you can cache the way you want to.
Use Redis, Memcached, the file system, or the database. The engine will cache repetitive data, such as nonces,
usage data, and static call responses. (You may shut off static call response caching in the configuration options.

In Progress
-----------

The features below are slated for development, though they aren't yet implemented.

**Call Scheduling** - Queue calls prioritized for later processing, and the engine will run the calls depending on
the server load. Set a cutoff time that stale calls need to be purged from the system. 

**Webhook Response Handling** - When you need a response relatively fast, though the response time of the remote
server may be too slow, use a webhook. Simply send the request to the call processing server to get a generic response.
If someone is waiting on the client side, run an AJAX request to check if the call processing server sent the response
to the webhook. If you're running the call for something on the backend, then simply process the response however
you'd like.

The Manual
----------

The engine is quite complex, though the code you write to interact with it is quite simple. Take a look at
[the manual](Manual.md) to see how to install the engine, and how to interact with it.

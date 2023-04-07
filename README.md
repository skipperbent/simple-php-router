# simple-router

Simple, fast and yet powerful PHP router that is easy to get integrated and in any project. Heavily inspired by the way Laravel handles routing, with both simplicity and expand-ability in mind.

With simple-router you can create a new project fast, without depending on a framework.

**It only takes a few lines of code to get started:**

```php
SimpleRouter::get('/', function() {
    return 'Hello world';
});
```

### Support the project

If you like simple-router and wish to see the continued development and maintenance of the project, please consider showing your support by buying me a coffee. Supporters will be listed under the credits section of this documentation.

You can donate any amount of your choice by [clicking here](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=NNX4D2RUSALCN).

## Table of Contents

- [Getting started](#getting-started)
	- [Notes](#notes-1)
	- [Requirements](#requirements)
	- [Features](#features)
	- [Installation](#installation)
		- [Setting up Apache](#setting-up-apache)
		- [Setting up Nginx](#setting-up-nginx)
		- [Setting up IIS](#setting-up-iis)
		- [Configuration](#configuration)
		- [Helper functions](#helper-functions)
- [Routes](#routes)
	- [Basic routing](#basic-routing)
		- [Class hinting](#class-hinting)
		- [Available methods](#available-methods)
		- [Multiple HTTP-verbs](#multiple-http-verbs)
	- [Route parameters](#route-parameters)
        - [Required parameters](#required-parameters)
        - [Optional parameters](#optional-parameters)
        - [Including slash in parameters](#including-slash-in-parameters)
        - [Regular expression constraints](#regular-expression-constraints)
        - [Regular expression route-match](#regular-expression-route-match)
        - [Custom regex for matching parameters](#custom-regex-for-matching-parameters)
	- [Named routes](#named-routes)
		- [Generating URLs To Named Routes](#generating-urls-to-named-routes)
	- [Router groups](#router-groups)
		- [Middleware](#middleware)
		- [Namespaces](#namespaces)
		- [Subdomain-routing](#subdomain-routing)
		- [Route prefixes](#route-prefixes)
	- [Partial groups](#partial-groups)
	- [Form Method Spoofing](#form-method-spoofing)
	- [Accessing The Current Route](#accessing-the-current-route)
	- [Other examples](#other-examples)
- [CSRF-protection](#csrf-protection)
	- [Adding CSRF-verifier](#adding-csrf-verifier)
	- [Getting CSRF-token](#getting-csrf-token)
	- [Custom CSRF-verifier](#custom-csrf-verifier)
	- [Custom Token-provider](#custom-token-provider)
- [Middlewares](#middlewares)
	- [Example](#example-1)
- [ExceptionHandlers](#exceptionhandlers)
    - [Handling 404, 403 and other errors](#handling-404-403-and-other-errors)
	- [Using custom exception handlers](#using-custom-exception-handlers)
		- [Prevent merge of parent exception-handlers](#prevent-merge-of-parent-exception-handlers)
- [Urls](#urls)
    - [Get the current url](#get-the-current-url)
 	- [Get by name (single route)](#get-by-name-single-route)
 	- [Get by name (controller route)](#get-by-name-controller-route)
 	- [Get by class](#get-by-class)
 	- [Using custom names for methods on a controller/resource route](#using-custom-names-for-methods-on-a-controllerresource-route)
 	- [Getting REST/resource controller urls](#getting-restresource-controller-urls)
 	- [Manipulating url](#manipulating-url)
 	- [Useful url tricks](#useful-url-tricks)
- [Input & parameters](#input--parameters)
    - [Using the Input class to manage parameters](#using-the-input-class-to-manage-parameters)
	    - [Get single parameter value](#get-single-parameter-value)
	    - [Get parameter object](#get-parameter-object)
	    - [Managing files](#managing-files)
	    - [Get all parameters](#get-all-parameters)
	    - [Check if parameters exists](#check-if-parameters-exists)
- [Events](#events)
    - [Available events](#available-events)
    - [Registering new event](#registering-new-event)
    - [Custom EventHandlers](#custom-eventhandlers)
- [Advanced](#advanced)
	- [Multiple route rendering](#multiple-route-rendering)
	- [Restrict access to IP](#restrict-access-to-ip)
	- [Setting custom base path](#setting-custom-base-path)
	- [Url rewriting](#url-rewriting)
		- [Changing current route](#changing-current-route)
		- [Bootmanager: loading routes dynamically](#bootmanager-loading-routes-dynamically)
		- [Adding routes manually](#adding-routes-manually)
	- [Custom class-loader](#custom-class-loader)
	  - [Integrating with php-di](#Integrating-with-php-di)
	- [Parameters](#parameters)
	- [Extending](#extending)
- [Help and support](#help-and-support)
    - [Common issues and fixes](#common-issues-and-fixes)
        - [Multiple routes matches? Which one has the priority?](#multiple-routes-matches-which-one-has-the-priority)
        - [Parameters won't match or route not working with special characters](#parameters-wont-match-or-route-not-working-with-special-characters)
        - [Using the router on sub-paths](#using-the-router-on-sub-paths)
    - [Debugging](#debugging)
        - [Creating unit-tests](#creating-unit-tests) 
        - [Debug information](#debug-information)
        - [Benchmark and log-info](#benchmark-and-log-info)
    - [Reporting a new issue](#reporting-a-new-issue)
        - [Procedure for reporting a new issue](#procedure-for-reporting-a-new-issue)
        - [Issue template](#issue-template)
    - [Feedback and development](#feedback-and-development)
   	    - [Contribution development guidelines](#contribution-development-guidelines)
- [Credits](#credits)
	- [Sites](#sites)
	- [License](#license)

___

# Getting started

Add the latest version of the simple-router project running this command.

```
composer require pecee/simple-router
```

## Notes

The goal of this project is to create a router that is more or less 100% compatible with the Laravel documentation, while remaining as simple as possible, and as easy to integrate and change without compromising either speed or complexity. Being lightweight is the #1 priority.

We've included a simple demo project for the router which can be found [here](https://github.com/skipperbent/simple-router-demo). This project should give you a basic understanding of how to setup and use simple-php-router project.

Please note that the demo-project only covers how to integrate the `simple-php-router` in a project without an existing framework. If you are using a framework in your project, the implementation might vary.

You can find the demo-project here: [https://github.com/skipperbent/simple-router-demo](https://github.com/skipperbent/simple-router-demo)

**What we won't cover:**

- How to setup a solution that fits your need. This is a basic demo to help you get started.
- Understanding of MVC; including Controllers, Middlewares or ExceptionHandlers.
- How to integrate into third party frameworks.

**What we cover:**

- How to get up and running fast - from scratch.
- How to get ExceptionHandlers, Middlewares and Controllers working.
- How to setup your webservers.

## Requirements

- PHP 7.1 or greater (version 3.x and below supports PHP 5.5+)
- PHP JSON extension enabled.

## Features

- Basic routing (`GET`, `POST`, `PUT`, `PATCH`, `UPDATE`, `DELETE`) with support for custom multiple verbs.
- Regular Expression Constraints for parameters.
- Named routes.
- Generating url to routes.
- Route groups.
- Middleware (classes that intercepts before the route is rendered).
- Namespaces.
- Route prefixes.
- CSRF protection.
- Optional parameters
- Sub-domain routing
- Custom boot managers to rewrite urls to "nicer" ones.
- Input manager; easily manage `GET`, `POST` and `FILE` values.
- IP based restrictions.
- Easily extendable.

## Installation

1. Navigate to your project folder in terminal and run the following command:

```php
composer require pecee/simple-router
```

### Setting up Nginx

If you are using Nginx please make sure that url-rewriting is enabled.

You can easily enable url-rewriting by adding the following configuration for the Nginx configuration-file for the demo-project.

```
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Setting up Apache

Nothing special is required for Apache to work. We've include the `.htaccess` file in the `public` folder. If rewriting is not working for you, please check that the `mod_rewrite` module (htaccess support) is enabled in the Apache configuration.

#### .htaccess example

Below is an example of an working `.htaccess` file used by simple-php-router.

Simply create a new `.htaccess` file in your projects `public` directory and paste the contents below in your newly created file. This will redirect all requests to your `index.php` file (see Configuration section below).

```
RewriteEngine on
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteCond %{SCRIPT_FILENAME} !-l
RewriteRule ^(.*)$ index.php/$1
```

### Setting up IIS

On IIS you have to add some lines your `web.config` file in the `public` folder or create a new one. If rewriting is not working for you, please check that your IIS version have included the `url rewrite` module or download and install them from Microsoft web site.

#### web.config example

Below is an example of an working `web.config` file used by simple-php-router.

Simply create a new `web.config` file in your projects `public` directory and paste the contents below in your newly created file. This will redirect all requests to your `index.php` file (see Configuration section below). If the `web.config` file already exists, add the `<rewrite>` section inside the `<system.webServer>` branch.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
	<rewrite>
	  <rules>
		<!-- Remove slash '/' from the en of the url -->
		<rule name="RewriteRequestsToPublic">
		  <match url="^(.*)$" />
		  <conditions logicalGrouping="MatchAll" trackAllCaptures="false">
		  </conditions>
		  <action type="Rewrite" url="/{R:0}" />
		</rule>

		<!-- When requested file or folder don't exists, will request again through index.php -->
		<rule name="Imported Rule 1" stopProcessing="true">
		  <match url="^(.*)$" ignoreCase="true" />
		  <conditions logicalGrouping="MatchAll">
			<add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
			<add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
		  </conditions>
		  <action type="Rewrite" url="/index.php/{R:1}" appendQueryString="true" />
		</rule>
	  </rules>
	</rewrite>
    </system.webServer>
</configuration>
```

#### Troubleshooting

If you do not have a `favicon.ico` file in your project, you can get a `NotFoundHttpException` (404 - not found).

To add `favicon.ico` to the IIS ignore-list, add the following line to the `<conditions>` group:

```
<add input="{REQUEST_FILENAME}" negate="true" pattern="favicon.ico" ignoreCase="true" />
```

You can also make one exception for files with some extensions:

```
<add input="{REQUEST_FILENAME}" pattern="\.ico|\.png|\.css|\.jpg" negate="true" ignoreCase="true" />
```

If you are using `$_SERVER['ORIG_PATH_INFO']`, you will get `\index.php\` as part of the returned value. 

**Example:**

```
/index.php/test/mypage.php
```

### Configuration

Create a new file, name it `routes.php` and place it in your library folder. This will be the file where you define all the routes for your project.

**WARNING: NEVER PLACE YOUR ROUTES.PHP IN YOUR PUBLIC FOLDER!**

In your ```index.php``` require your newly-created ```routes.php``` and call the ```SimpleRouter::start()``` method. This will trigger and do the actual routing of the requests.

It's not required, but you can set `SimpleRouter::setDefaultNamespace('\Demo\Controllers');` to prefix all routes with the namespace to your controllers. This will simplify things a bit, as you won't have to specify the namespace for your controllers on each route.

**This is an example of a basic ```index.php``` file:**

```php
<?php
use Pecee\SimpleRouter\SimpleRouter;

/* Load external routes file */
require_once 'routes.php';

/**
 * The default namespace for route-callbacks, so we don't have to specify it each time.
 * Can be overwritten by using the namespace config option on your routes.
 */

SimpleRouter::setDefaultNamespace('\Demo\Controllers');

// Start the routing
SimpleRouter::start();
```

### Helper functions

We recommend that you add these helper functions to your project. These will allow you to access functionality of the router more easily.

To implement the functions below, simply copy the code to a new file and require the file before initializing the router or copy the `helpers.php` we've included in this library.

```php
use Pecee\SimpleRouter\SimpleRouter as Router;
use Pecee\Http\Url;
use Pecee\Http\Response;
use Pecee\Http\Request;

/**
 * Get url for a route by using either name/alias, class or method name.
 *
 * The name parameter supports the following values:
 * - Route name
 * - Controller/resource name (with or without method)
 * - Controller class name
 *
 * When searching for controller/resource by name, you can use this syntax "route.name@method".
 * You can also use the same syntax when searching for a specific controller-class "MyController@home".
 * If no arguments is specified, it will return the url for the current loaded route.
 *
 * @param string|null $name
 * @param string|array|null $parameters
 * @param array|null $getParams
 * @return \Pecee\Http\Url
 * @throws \InvalidArgumentException
 */
function url(?string $name = null, $parameters = null, ?array $getParams = null): Url
{
    return Router::getUrl($name, $parameters, $getParams);
}

/**
 * @return \Pecee\Http\Response
 */
function response(): Response
{
    return Router::response();
}

/**
 * @return \Pecee\Http\Request
 */
function request(): Request
{
    return Router::request();
}

/**
 * Get input class
 * @param string|null $index Parameter index name
 * @param string|mixed|null $defaultValue Default return value
 * @param array ...$methods Default methods
 * @return \Pecee\Http\Input\InputHandler|array|string|null
 */
function input($index = null, $defaultValue = null, ...$methods)
{
    if ($index !== null) {
        return request()->getInputHandler()->value($index, $defaultValue, ...$methods);
    }

    return request()->getInputHandler();
}

/**
 * @param string $url
 * @param int|null $code
 */
function redirect(string $url, ?int $code = null): void
{
    if ($code !== null) {
        response()->httpCode($code);
    }

    response()->redirect($url);
}

/**
 * Get current csrf-token
 * @return string|null
 */
function csrf_token(): ?string
{
    $baseVerifier = Router::router()->getCsrfVerifier();
    if ($baseVerifier !== null) {
        return $baseVerifier->getTokenProvider()->getToken();
    }

    return null;
}
```

---

# Routes

Remember the ```routes.php``` file you required in your ```index.php```? This file be where you place all your custom rules for routing.

## Basic routing

Below is a very basic example of setting up a route. First parameter is the url which the route should match - next parameter is a `Closure` or callback function that will be triggered once the route matches.

```php
SimpleRouter::get('/', function() {
    return 'Hello world';
});
```

### Class hinting

You can use class hinting to load a class & method like this:

```php
SimpleRouter::get('/', [MyClass::class, 'myMethod']);
```

### Available methods

Here you can see a list over all available routes:

```php
SimpleRouter::get($url, $callback, $settings);
SimpleRouter::post($url, $callback, $settings);
SimpleRouter::put($url, $callback, $settings);
SimpleRouter::patch($url, $callback, $settings);
SimpleRouter::delete($url, $callback, $settings);
SimpleRouter::options($url, $callback, $settings);
```

### Multiple HTTP-verbs

Sometimes you might need to create a route that accepts multiple HTTP-verbs. If you need to match all HTTP-verbs you can use the `any` method.

```php
SimpleRouter::match(['get', 'post'], '/', function() {
    // ...
});

SimpleRouter::any('foo', function() {
    // ...
});
```

We've created a simple method which matches `GET` and `POST` which is most commonly used:

```php
SimpleRouter::form('foo', function() {
    // ...
});
```

## Route parameters

### Required parameters

You'll properly wondering by know how you parse parameters from your urls. For example, you might want to capture the users id from an url. You can do so by defining route-parameters.

```php
SimpleRouter::get('/user/{id}', function ($userId) {
    return 'User with id: ' . $userId;
});
```

You may define as many route parameters as required by your route:

```php
SimpleRouter::get('/posts/{post}/comments/{comment}', function ($postId, $commentId) {
    // ...
});
```

**Note:** Route parameters are always encased within `{` `}` braces and should consist of alphabetic characters. Route parameters can only contain certain characters like `A-Z`, `a-z`, `0-9`, `-` and `_`.
If your route contain other characters, please see  [Custom regex for matching parameters](#custom-regex-for-matching-parameters).

### Optional parameters

Occasionally you may need to specify a route parameter, but make the presence of that route parameter optional. You may do so by placing a ? mark after the parameter name. Make sure to give the route's corresponding variable a default value:

```php
SimpleRouter::get('/user/{name?}', function ($name = null) {
    return $name;
});

SimpleRouter::get('/user/{name?}', function ($name = 'Simon') {
    return $name;
});
```

### Including slash in parameters

If you're working with WebDAV services the url could mean the difference between a file and a folder.

For instance `/path` will be considered a file - whereas `/path/` will be considered a folder.

The router can add the ending slash for the last parameter in your route based on the path. So if `/path/` is requested the parameter will contain the value of `path/` and visa versa.

To ensure compatibility with older versions, this feature is disabled by default and has to be enabled by setting 
the `setSettings(['includeSlash' => true])` or by using setting `setSlashParameterEnabled(true)` for your route.

**Example**

```php
SimpleRouter::get('/path/{fileOrFolder}', function ($fileOrFolder) {
	return $fileOrFolder;
})->setSettings(['includeSlash' => true]);
```

- Requesting `/path/file` will return the `$fileOrFolder` value: `file`.
- Requesting `/path/folder/` will return the `$fileOrFolder` value: `folder/`.

### Regular expression constraints

You may constrain the format of your route parameters using the where method on a route instance. The where method accepts the name of the parameter and a regular expression defining how the parameter should be constrained:

```php
SimpleRouter::get('/user/{name}', function ($name) {
    
    // ... do stuff
    
})->where([ 'name' => '[A-Za-z]+' ]);

SimpleRouter::get('/user/{id}', function ($id) {
    
    // ... do stuff
    
})->where([ 'id' => '[0-9]+' ]);

SimpleRouter::get('/user/{id}/{name}', function ($id, $name) {
    
    // ... do stuff
    
})->where(['id' => '[0-9]+', 'name' => '[a-z]+']);
```

### Regular expression route-match

You can define a regular-expression match for the entire route if you wish.

This is useful if you for example are creating a model-box which loads urls from ajax.

The example below is using the following regular expression: `/ajax/([\w]+)/?([0-9]+)?/?` which basically just matches `/ajax/` and exspects the next parameter to be a string - and the next to be a number (but optional).

**Matches:** `/ajax/abc/`, `/ajax/abc/123/`

**Won't match:** `/ajax/`

Match groups specified in the regex will be passed on as parameters:

```php
SimpleRouter::all('/ajax/abc/123', function($param1, $param2) {
	// param1 = abc
	// param2 = 123
})->setMatch('/\/ajax\/([\w]+)\/?([0-9]+)?\/?/is');
```

### Custom regex for matching parameters

By default simple-php-router uses the `[\w\-]+` regular expression. It will match `A-Z`, `a-z`, `0-9`, `-` and `_` characters in parameters.
This decision was made with speed and reliability in mind, as this match will match both letters, number and most of the used symbols on the internet.

However, sometimes it can be necessary to add a custom regular expression to match more advanced characters like foreign letters `æ ø å` etc.

You can test your custom regular expression by using on the site [Regex101.com](https://www.regex101.com).

Instead of adding a custom regular expression to all your parameters, you can simply add a global regular expression which will be used on all the parameters on the route.

**Note:** If you the regular expression to be available across, we recommend using the global parameter on a group as demonstrated in the examples below.

#### Example

This example will ensure that all parameters use the `[\w\-\æ\ø\å]+` (`a-z`, `A-Z`, `-`, `_`, `0-9`, `æ`, `ø`, `å`) regular expression when parsing.

```php
SimpleRouter::get('/path/{parameter}', 'VideoController@home', ['defaultParameterRegex' => '[\w\-\æ\ø\å]+']);
```

You can also apply this setting to a group if you need multiple routes to use your custom regular expression when parsing parameters.

```php
SimpleRouter::group(['defaultParameterRegex' => '[\w\-\æ\ø\å]+'], function() {

    SimpleRouter::get('/path/{parameter}', 'VideoController@home');

});
```

## Named routes

Named routes allow the convenient generation of URLs or redirects for specific routes. You may specify a name for a route by chaining the name method onto the route definition:

```php
SimpleRouter::get('/user/profile', function () {
    // Your code here
})->name('profile');
```

You can also specify names for Controller-actions:

```php
SimpleRouter::get('/user/profile', 'UserController@profile')->name('profile');
```

### Generating URLs To Named Routes

Once you have assigned a name to a given route, you may use the route's name when generating URLs or redirects via the global `url` helper-function (see helpers section):

```php
// Generating URLs...
$url = url('profile');
```

If the named route defines parameters, you may pass the parameters as the second argument to the `url` function. The given parameters will automatically be inserted into the URL in their correct positions:

```php
SimpleRouter::get('/user/{id}/profile', function ($id) {
    //
})->name('profile');

$url = url('profile', ['id' => 1]);
```

For more information on urls, please see the [Urls](#urls) section.

## Router groups

Route groups allow you to share route attributes, such as middleware or namespaces, across a large number of routes without needing to define those attributes on each individual route. Shared attributes are specified in an array format as the first parameter to the `SimpleRouter::group` method.

### Middleware

To assign middleware to all routes within a group, you may use the middleware key in the group attribute array. Middleware are executed in the order they are listed in the array:

```php
SimpleRouter::group(['middleware' => \Demo\Middleware\Auth::class], function () {
    SimpleRouter::get('/', function ()    {
        // Uses Auth Middleware
    });

    SimpleRouter::get('/user/profile', function () {
        // Uses Auth Middleware
    });
});
```

### Namespaces

Another common use-case for route groups is assigning the same PHP namespace to a group of controllers using the `namespace` parameter in the group array:

#### Note
Group namespaces will only be added to routes with relative callbacks.
For example if your route has an absolute callback like `\Demo\Controller\DefaultController@home`, the namespace from the route will not be prepended.
To fix this you can make the callback relative by removing the `\` in the beginning of the callback.

```php
SimpleRouter::group(['namespace' => 'Admin'], function () {
    // Controllers Within The "App\Http\Controllers\Admin" Namespace
});
```

You can add parameters to the prefixes of your routes.

Parameters from your previous routes will be injected 
into your routes after any route-required parameters, starting from oldest to newest.

```php
SimpleRouter::group(['prefix' => '/lang/{lang}'], function ($language) {
    
    SimpleRouter::get('/about', function($language) {
    	
    	// Will match /lang/da/about
    	
    });
    
});
```

### Subdomain-routing

Route groups may also be used to handle sub-domain routing. Sub-domains may be assigned route parameters just like route urls, allowing you to capture a portion of the sub-domain for usage in your route or controller. The sub-domain may be specified using the `domain` key on the group attribute array:

```php
SimpleRouter::group(['domain' => '{account}.myapp.com'], function () {
    SimpleRouter::get('/user/{id}', function ($account, $id) {
        //
    });
});
```

### Route prefixes

The `prefix` group attribute may be used to prefix each route in the group with a given url. For example, you may want to prefix all route urls within the group with `admin`:

```php
SimpleRouter::group(['prefix' => '/admin'], function () {
    SimpleRouter::get('/users', function ()    {
        // Matches The "/admin/users" URL
    });
});
```

You can also use parameters in your groups:

```php
SimpleRouter::group(['prefix' => '/lang/{language}'], function ($language) {
    SimpleRouter::get('/users', function ($language)    {
        // Matches The "/lang/da/users" URL
    });
});
```

## Partial groups

Partial router groups has the same benefits as a normal group, but **are only rendered once the url has matched** 
in contrast to a normal group which are always rendered in order to retrieve it's child routes.
Partial groups are therefore more like a hybrid of a traditional route with the benefits of a group.

This can be extremely useful in situations where you only want special routes to be added, but only when a certain criteria or logic has been met.

**NOTE:** Use partial groups with caution as routes added within are only rendered and available once the url of the partial-group has matched. 
This can cause `url()` not to find urls for the routes added within before the partial-group has been matched and is rendered.

**Example:**

```php
SimpleRouter::partialGroup('/plugin/{name}', function ($plugin) {

    // Add routes from plugin

});
```

## Form Method Spoofing

HTML forms do not support `PUT`, `PATCH` or `DELETE` actions. So, when defining `PUT`, `PATCH` or `DELETE` routes that are called from an HTML form, you will need to add a hidden `_method` field to the form. The value sent with the `_method` field will be used as the HTTP request method:

```php
<input type="hidden" name="_method" value="PUT" />
```

## Accessing The Current Route

You can access information about the current route loaded by using the following method:

```php
SimpleRouter::request()->getLoadedRoute();
request()->getLoadedRoute();
```

## Other examples

You can find many more examples in the `routes.php` example-file below:

```php
<?php
use Pecee\SimpleRouter\SimpleRouter;

/* Adding custom csrfVerifier here */
SimpleRouter::csrfVerifier(new \Demo\Middlewares\CsrfVerifier());

SimpleRouter::group(['middleware' => \Demo\Middlewares\Site::class, 'exceptionHandler' => \Demo\Handlers\CustomExceptionHandler::class], function() {


    SimpleRouter::get('/answers/{id}', 'ControllerAnswers@show', ['where' => ['id' => '[0-9]+']]);

	/**
     * Class hinting is supported too
     */
     
     SimpleRouter::get('/answers/{id}', [ControllerAnswers::class, 'show'], ['where' => ['id' => '[0-9]+']]);

    /**
     * Restful resource (see IRestController interface for available methods)
     */

    SimpleRouter::resource('/rest', ControllerResource::class);


    /**
     * Load the entire controller (where url matches method names - getIndex(), postIndex(), putIndex()).
     * The url paths will determine which method to render.
     *
     * For example:
     *
     * GET  /animals         => getIndex()
     * GET  /animals/view    => getView()
     * POST /animals/save    => postSave()
     *
     * etc.
     */

    SimpleRouter::controller('/animals', ControllerAnimals::class);

});

SimpleRouter::get('/page/404', 'ControllerPage@notFound', ['as' => 'page.notfound']);
```

---

# CSRF Protection

Any forms posting to `POST`, `PUT` or `DELETE` routes should include the CSRF-token. We strongly recommend that you enable CSRF-verification on your site to maximize security.

You can use the `BaseCsrfVerifier` to enable CSRF-validation on all request. If you need to disable verification for specific urls, please refer to the "Custom CSRF-verifier" section below.

By default simple-php-router will use the `CookieTokenProvider` class. This provider will store the security-token in a cookie on the clients machine.
If you want to store the token elsewhere, please refer to the "Creating custom Token Provider" section below.

## Adding CSRF-verifier

When you've created your CSRF-verifier you need to tell simple-php-router that it should use it. You can do this by adding the following line in your `routes.php` file:

```php
SimpleRouter::csrfVerifier(new \Demo\Middlewares\CsrfVerifier());
```

## Getting CSRF-token

When posting to any of the urls that has CSRF-verification enabled, you need post your CSRF-token or else the request will get rejected.

You can get the CSRF-token by calling the helper method:

```php
csrf_token();
```

You can also get the token directly:

```php
return SimpleRouter::router()->getCsrfVerifier()->getTokenProvider()->getToken();
```

The default name/key for the input-field is `csrf_token` and is defined in the `POST_KEY` constant in the `BaseCsrfVerifier` class.
You can change the key by overwriting the constant in your own CSRF-verifier class.

**Example:**

The example below will post to the current url with a hidden field "`csrf_token`".

```html
<form method="post" action="<?= url(); ?>">
    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
    <!-- other input elements here -->
</form>
```

## Custom CSRF-verifier

Create a new class and extend the `BaseCsrfVerifier` middleware class provided by default with the simple-php-router library.

Add the property `except` with an array of the urls to the routes you want to exclude/whitelist from the CSRF validation.
Using ```*``` at the end for the url will match the entire url.

**Here's a basic example on a CSRF-verifier class:**

```php
namespace Demo\Middlewares;

use Pecee\Http\Middleware\BaseCsrfVerifier;

class CsrfVerifier extends BaseCsrfVerifier
{
	/**
	 * CSRF validation will be ignored on the following urls.
	 */
	protected $except = ['/api/*'];
}
```

## Custom Token Provider

By default the `BaseCsrfVerifier` will use the `CookieTokenProvider` to store the token in a cookie on the clients machine.

If you need to store the token elsewhere, you can do that by creating your own class and implementing the `ITokenProvider` class.

```php
class SessionTokenProvider implements ITokenProvider
{

    /**
     * Refresh existing token
     */
    public function refresh(): void
    {
        // Implement your own functionality here...
    }

    /**
     * Validate valid CSRF token
     *
     * @param string $token
     * @return bool
     */
    public function validate($token): bool
    {
        // Implement your own functionality here...
    }
    
    /**
     * Get token token
     *
     * @param string|null $defaultValue
     * @return string|null
     */
    public function getToken(?string $defaultValue = null): ?string 
    {
        // Implement your own functionality here...
    }

}
```

Next you need to set your custom `ITokenProvider` implementation on your `BaseCsrfVerifier` class in your routes file:

```php
$verifier = new \Demo\Middlewares\CsrfVerifier();
$verifier->setTokenProvider(new SessionTokenProvider());

SimpleRouter::csrfVerifier($verifier);
```

---

# Middlewares

Middlewares are classes that loads before the route is rendered. A middleware can be used to verify that a user is logged in - or to set parameters specific for the current request/route. Middlewares must implement the `IMiddleware` interface.

## Example

```php
namespace Demo\Middlewares;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;

class CustomMiddleware implements IMiddleware {

    public function handle(Request $request): void 
    {
    
        // Authenticate user, will be available using request()->user
        $request->user = User::authenticate();

        // If authentication failed, redirect request to user-login page.
        if($request->user === null) {
            $request->setRewriteUrl(url('user.login'));
        }

    }
}
```

---

# ExceptionHandlers

ExceptionHandler are classes that handles all exceptions. ExceptionsHandlers must implement the `IExceptionHandler` interface.

## Handling 404, 403 and other errors

If you simply want to catch a 404 (page not found) etc. you can use the `SimpleRouter::error($callback)` static helper method.

This will add a callback method which is fired whenever an error occurs on all routes.

The basic example below simply redirect the page to `/not-found` if an `NotFoundHttpException` (404) occurred.
The code should be placed in the file that contains your routes.

```php
SimpleRouter::get('/not-found', 'PageController@notFound');
SimpleRouter::get('/forbidden', 'PageController@notFound');

SimpleRouter::error(function(Request $request, \Exception $exception) {

    switch($exception->getCode()) {
        // Page not found
        case 404:
            response()->redirect('/not-found');
        // Forbidden
        case 403:
            response()->redirect('/forbidden');
    }
    
});
```

The example above will redirect all errors with http-code `404` (page not found) to `/not-found` and `403` (forbidden) to `/forbidden`.

If you do not want a redirect, but want the error-page rendered on the current-url, you can tell the router to execute a rewrite callback like so:

```php
$request->setRewriteCallback('ErrorController@notFound');
```

If you will set the correct status for the browser error use:

```php
SimpleRouter::response()->httpCode(404);
```

## Using custom exception handlers

This is a basic example of an ExceptionHandler implementation (please see "[Easily overwrite route about to be loaded](#easily-overwrite-route-about-to-be-loaded)" for examples on how to change callback).

```php
namespace Demo\Handlers;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Handlers\IExceptionHandler;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

class CustomExceptionHandler implements IExceptionHandler
{
	public function handleError(Request $request, \Exception $error): void
	{

		/* You can use the exception handler to format errors depending on the request and type. */

		if ($request->getUrl()->contains('/api')) {

			response()->json([
				'error' => $error->getMessage(),
				'code'  => $error->getCode(),
			]);

		}

		/* The router will throw the NotFoundHttpException on 404 */
		if($error instanceof NotFoundHttpException) {

			// Render custom 404-page
			$request->setRewriteCallback('Demo\Controllers\PageController@notFound');
			return;
			
		}
		
		/* Other error */
		if($error instanceof MyCustomException) {

			$request->setRewriteRoute(
				// Add new route based on current url (minus query-string) and add custom parameters.
				(new RouteUrl(url(null, null, []), 'PageController@error'))->setParameters(['exception' => $error])
			);
			return;
			
		}

		throw $error;

	}

}
```

You can add your custom exception-handler class to your group by using the `exceptionHandler` settings-attribute.
`exceptionHandler` can be either class-name or array of class-names.

```php
SimpleRouter::group(['exceptionHandler' => \Demo\Handlers\CustomExceptionHandler::class], function() {

    // Your routes here

});
```

### Prevent merge of parent exception-handlers

By default the router will merge exception-handlers to any handlers provided by parent groups, and will be executed in the order of newest to oldest.

If you want your groups exception handler to be executed independently, you can add the `mergeExceptionHandlers` attribute and set it to `false`.

```php
SimpleRouter::group(['prefix' => '/', 'exceptionHandler' => \Demo\Handlers\FirstExceptionHandler::class, 'mergeExceptionHandlers' => false], function() {

	SimpleRouter::group(['prefix' => '/admin', 'exceptionHandler' => \Demo\Handlers\SecondExceptionHandler::class], function() {
	
		// Both SecondExceptionHandler and FirstExceptionHandler will trigger (in that order).
	
	});
	
	SimpleRouter::group(['prefix' => '/user', 'exceptionHandler' => \Demo\Handlers\SecondExceptionHandler::class, 'mergeExceptionHandlers' => false], function() {
	
		// Only SecondExceptionHandler will trigger.
	
	});

});
```

---

# Urls

By default all controller and resource routes will use a simplified version of their url as name.

You easily use the `url()` shortcut helper function to retrieve urls for your routes or manipulate the current url.

`url()` will return a `Url` object which will return a `string` when rendered, so it can be used safely in templates etc. but 
contains all the useful helpers methods in the `Url` class like `contains`, `indexOf` etc. 
Check the [Useful url tricks](#useful-url-tricks) below.

### Get the current url

It has never been easier to get and/or manipulate the current url.

The example below shows you how to get the current url:

```php
# output: /current-url
url();
```

### Get by name (single route)

```php
SimpleRouter::get('/product-view/{id}', 'ProductsController@show', ['as' => 'product']);

# output: /product-view/22/?category=shoes
url('product', ['id' => 22], ['category' => 'shoes']);

# output: /product-view/?category=shoes
url('product', null, ['category' => 'shoes']);
```

### Get by name (controller route)

```php
SimpleRouter::controller('/images', ImagesController::class, ['as' => 'picture']);

# output: /images/view/?category=shows
url('picture@getView', null, ['category' => 'shoes']);

# output: /images/view/?category=shows
url('picture', 'getView', ['category' => 'shoes']);

# output: /images/view/
url('picture', 'view');
```

### Get by class

```php
SimpleRouter::get('/product-view/{id}', 'ProductsController@show', ['as' => 'product']);
SimpleRouter::controller('/images', 'ImagesController');

# output: /product-view/22/?category=shoes
url('ProductsController@show', ['id' => 22], ['category' => 'shoes']);

# output: /images/image/?id=22
url('ImagesController@getImage', null, ['id' => 22]);
```

### Using custom names for methods on a controller/resource route

```php
SimpleRouter::controller('gadgets', GadgetsController::class, ['names' => ['getIphoneInfo' => 'iphone']]);

url('gadgets.iphone');

# output
# /gadgets/iphoneinfo/
```

### Getting REST/resource controller urls

```php
SimpleRouter::resource('/phones', PhonesController::class);

# output: /phones/
url('phones');

# output: /phones/
url('phones.index');

# output: /phones/create/
url('phones.create');

# output: /phones/edit/
url('phones.edit');
```

### Manipulating url

You can easily manipulate the query-strings, by adding your get param arguments.

```php
# output: /current-url?q=cars

url(null, null, ['q' => 'cars']);
```

You can remove a query-string parameter by setting the value to `null`. 

The example below will remove any query-string parameter named `q` from the url but keep all others query-string parameters:

```php
$url = url()->removeParam('q');
```

For more information please check the [Useful url tricks](#useful-url-tricks) section of the documentation.

### Useful url tricks

Calling `url` will always return a `Url` object. Upon rendered it will return a `string` of the relative `url`, so it's safe to use in templates etc.

However this allow us to use the useful methods on the `Url` object like `indexOf` and `contains` or retrieve specific parts of the url like the path, querystring parameters, host etc. You can also manipulate the url like removing- or adding parameters, changing host and more.

In the example below, we check if the current url contains the `/api` part.

```php
if(url()->contains('/api')) {
    // ... do stuff
}
```

As mentioned earlier, you can also use the `Url` object to show specific parts of the url or control what part of the url you want.

```php
# Grab the query-string parameter id from the current-url.
$id = url()->getParam('id');

# Get the absolute url for the current url.
$absoluteUrl = url()->getAbsoluteUrl();
```

For more available methods please check the `Pecee\Http\Url` class.

# Input & parameters

simple-router offers libraries and helpers that makes it easy to manage and manipulate input-parameters like `$_POST`, `$_GET` and `$_FILE`.

## Using the Input class to manage parameters

You can use the `InputHandler` class to easily access and manage parameters from your request. The `InputHandler` class offers extended features such as copying/moving uploaded files directly on the object, getting file-extension, mime-type etc.

### Get single parameter value

```input($index, $defaultValue, ...$methods);```

To quickly get a value from a parameter, you can use the `input` helper function.

This will automatically trim the value and ensure that it's not empty. If it's empty the `$defaultValue` will be returned instead.

**Note:** 
This function returns a `string` unless the parameters are grouped together, in that case it will return an `array` of values.

**Example:**

This example matches both POST and GET request-methods and if name is empty the default-value "Guest" will be returned. 

```php
$name = input('name', 'Guest', 'post', 'get');
```

### Get parameter object

When dealing with file-uploads it can be useful to retrieve the raw parameter object.

**Search for object with default-value across multiple or specific request-methods:**

The example below will return an `InputItem` object if the parameter was found or return the `$defaultValue`. If parameters are grouped, it will return an array of `InputItem` objects.

```php
$object = input()->find($index, $defaultValue = null, ...$methods);
```

**Getting specific `$_GET` parameter as `InputItem` object:**

The example below will return an `InputItem` object if the parameter was found or return the `$defaultValue`. If parameters are grouped, it will return an array of `InputItem` objects.

```php
$object = input()->get($index, $defaultValue = null);
```

**Getting specific `$_POST` parameter as `InputItem` object:**

The example below will return an `InputItem` object if the parameter was found or return the `$defaultValue`. If parameters are grouped, it will return an array of `InputItem` objects.

```php
$object = input()->post($index, $defaultValue = null);
```

**Getting specific `$_FILE` parameter as `InputFile` object:**

The example below will return an `InputFile` object if the parameter was found or return the `$defaultValue`. If parameters are grouped, it will return an array of `InputFile` objects.

```php
$object = input()->file($index, $defaultValue = null);
```

### Managing files

```php
/**
 * Loop through a collection of files uploaded from a form on the page like this
 * <input type="file" name="images[]" />
 */

/* @var $image \Pecee\Http\Input\InputFile */
foreach(input()->file('images', []) as $image)
{
    if($image->getMime() === 'image/jpeg') 
    {
        $destinationFilname = sprintf('%s.%s', uniqid(), $image->getExtension());
        $image->move(sprintf('/uploads/%s', $destinationFilename));
    }
}

```

### Get all parameters

```php
# Get all
$values = input()->all();

# Only match specific keys
$values = input()->all([
    'company_name',
    'user_id'
]);
```

All object implements the `IInputItem` interface and will always contain these methods:

- `getIndex()` - returns the index/key of the input.
- `setIndex()` - set the index/key of the input.
- `getName()` - returns a human friendly name for the input (company_name will be Company Name etc).
- `setName()` - sets a human friendly name for the input (company_name will be Company Name etc).
- `getValue()` - returns the value of the input.
- `setValue()` - sets the value of the input.

`InputFile` has the same methods as above along with some other file-specific methods like:

- `getFilename` - get the filename.
- `getTmpName()` - get file temporary name.
- `getSize()` - get file size.
- `move($destination)` - move file to destination.
- `getContents()` - get file content.
- `getType()` - get mime-type for file.
- `getError()` - get file upload error.
- `hasError()` - returns `bool` if an error occurred while uploading (if `getError` is not 0).
- `toArray()` - returns raw array

---

### Check if parameters exists

You can easily if multiple items exists by using the `exists` method. It's simular to `value` as it can be used 
to filter on request-methods and supports both `string` and `array` as parameter value.

**Example:**

```php
if(input()->exists(['name', 'lastname'])) {
	// Do stuff
}

/* Similar to code above */
if(input()->exists('name') && input()->exists('lastname')) {
	// Do stuff
}
```

# Events

This section will help you understand how to register your own callbacks to events in the router.
It will also cover the basics of event-handlers; how to use the handlers provided with the router and how to create your own custom event-handlers.

## Available events

This section contains all available events that can be registered using the `EventHandler`.

All event callbacks will retrieve a `EventArgument` object as parameter. This object contains easy access to event-name, router- and request instance and any special event-arguments related to the given event. You can see what special event arguments each event returns in the list below.  

| Name                        | Special arguments | Description               | 
| -------------               |-----------      | ----                      | 
| `EVENT_ALL`                 | - | Fires when a event is triggered. |
| `EVENT_INIT`                | - | Fires when router is initializing and before routes are loaded. |
| `EVENT_LOAD`                | `loadedRoutes` | Fires when all routes has been loaded and rendered, just before the output is returned. |
| `EVENT_ADD_ROUTE`           | `route`<br>`isSubRoute` | Fires when route is added to the router. `isSubRoute` is true when sub-route is rendered. |
| `EVENT_REWRITE`             | `rewriteUrl`<br>`rewriteRoute` | Fires when a url-rewrite is and just before the routes are re-initialized. |
| `EVENT_BOOT`                | `bootmanagers` | Fires when the router is booting. This happens just before boot-managers are rendered and before any routes has been loaded. |
| `EVENT_RENDER_BOOTMANAGER`  | `bootmanagers`<br>`bootmanager` | Fires before a boot-manager is rendered. |
| `EVENT_LOAD_ROUTES`         | `routes` | Fires when the router is about to load all routes. |
| `EVENT_FIND_ROUTE`          | `name` | Fires whenever the `findRoute` method is called within the `Router`. This usually happens when the router tries to find routes that contains a certain url, usually after the `EventHandler::EVENT_GET_URL` event. |
| `EVENT_GET_URL`             | `name`<br>`parameters`<br>`getParams` | Fires whenever the `SimpleRouter::getUrl` method or `url`-helper function is called and the router tries to find the route. |
| `EVENT_MATCH_ROUTE`         | `route` | Fires when a route is matched and valid (correct request-type etc). and before the route is rendered. |
| `EVENT_RENDER_ROUTE`        | `route` | Fires before a route is rendered. |
| `EVENT_LOAD_EXCEPTIONS`     | `exception`<br>`exceptionHandlers` | Fires when the router is loading exception-handlers. |
| `EVENT_RENDER_EXCEPTION`    | `exception`<br>`exceptionHandler`<br>`exceptionHandlers` | Fires before the router is rendering a exception-handler. |
| `EVENT_RENDER_MIDDLEWARES`  | `route`<br>`middlewares` | Fires before middlewares for a route is rendered. |
| `EVENT_RENDER_CSRF`         | `csrfVerifier` | Fires before the CSRF-verifier is rendered. |

## Registering new event

To register a new event you need to create a new instance of the `EventHandler` object. On this object you can add as many callbacks as you like by calling the `registerEvent` method.

When you've registered events, make sure to add it to the router by calling 
`SimpleRouter::addEventHandler()`. We recommend that you add your event-handlers within your `routes.php`.

**Example:**

```php
use Pecee\SimpleRouter\Handlers\EventHandler;
use Pecee\SimpleRouter\Event\EventArgument;

// --- your routes goes here ---

$eventHandler = new EventHandler();

// Add event that fires when a route is rendered
$eventHandler->register(EventHandler::EVENT_RENDER_ROUTE, function(EventArgument $argument) {
   
   // Get the route by using the special argument for this event.
   $route = $argument->route;
   
   // DO STUFF...
    
});

SimpleRouter::addEventHandler($eventHandler);

```

## Custom EventHandlers

`EventHandler` is the class that manages events and must inherit from the `IEventHandler` interface. The handler knows how to handle events for the given handler-type. 

Most of the time the basic `\Pecee\SimpleRouter\Handler\EventHandler` class will be more than enough for most people as you simply register an event which fires when triggered.

Let's go over how to create your very own event-handler class.

Below is a basic example of a custom event-handler called `DatabaseDebugHandler`. The idea of the sample below is to logs all events to the database when triggered. Hopefully it will be enough to give you an idea on how the event-handlers work.

```php
namespace Demo\Handlers;

use Pecee\SimpleRouter\Event\EventArgument;
use Pecee\SimpleRouter\Router;

class DatabaseDebugHandler implements IEventHandler
{

    /**
     * Debug callback
     * @var \Closure
     */
    protected $callback;

    public function __construct()
    {
        $this->callback = function (EventArgument $argument) {
            // todo: store log in database
        };
    }

    /**
     * Get events.
     *
     * @param string|null $name Filter events by name.
     * @return array
     */
    public function getEvents(?string $name): array
    {
        return [
            $name => [
                $this->callback,
            ],
        ];
    }

    /**
     * Fires any events registered with given event-name
     *
     * @param Router $router Router instance
     * @param string $name Event name
     * @param array ...$eventArgs Event arguments
     */
    public function fireEvents(Router $router, string $name, ...$eventArgs): void
    {
        $callback = $this->callback;
        $callback(new EventArgument($router, $eventArgs));
    }

    /**
     * Set debug callback
     *
     * @param \Closure $event
     */
    public function setCallback(\Closure $event): void
    {
        $this->callback = $event;
    }

}
```

---

# Advanced

## Multiple route rendering

If you need multiple routes to be executed on the same url, you can enable this feature by setting `SimpleRouter::enableMultiRouteRendering(true)`
in your `routes.php` file.

This is most commonly used in advanced cases, for example in CMS systems where multiple routes needs to be rendered.

## Restrict access to IP

You can white and/or blacklist access to IP's using the build in `IpRestrictAccess` middleware.

Create your own custom Middleware and extend the `IpRestrictAccess` class.

The `IpRestrictAccess` class contains two properties `ipBlacklist` and `ipWhitelist` that can be added to your middleware to change which IP's that have access to your routes.

You can use `*` to restrict access to a range of ips.

```php
use \Pecee\Http\Middleware\IpRestrictAccess;

class IpBlockerMiddleware extends IpRestrictAccess 
{

    protected $ipBlacklist = [
        '5.5.5.5',
        '8.8.*',
    ];

    protected $ipWhitelist = [
        '8.8.2.2',
    ];

}
```

You can add the middleware to multiple routes by adding your [middleware to a group](#middleware).

## Setting custom base path

Sometimes it can be useful to add a custom base path to all of the routes added.

This can easily be done by taking advantage of the [Event Handlers](#events) support of the project.

```php
$basePath = '/basepath';

$eventHandler = new EventHandler();
$eventHandler->register(EventHandler::EVENT_ADD_ROUTE, function(EventArgument $event) use($basePath) {

	$route = $event->route;

	// Skip routes added by group as these will inherit the url
	if(!$event->isSubRoute) {
		return;
	}
	
	switch (true) {
		case $route instanceof ILoadableRoute:
			$route->prependUrl($basePath);
			break;
		case $route instanceof IGroupRoute:
			$route->prependPrefix($basePath);
			break;

	}
	
});

SimpleRouter::addEventHandler($eventHandler);
```

In the example shown above, we create a new `EVENT_ADD_ROUTE` event that triggers, when a new route is added.
We skip all subroutes as these will inherit the url from their parent. Then, if the route is a group, we change the prefix  
otherwise we change the url.

## Url rewriting

### Changing current route

Sometimes it can be useful to manipulate the route about to be loaded.
simple-php-router allows you to easily manipulate and change the routes which are about to be rendered.
All information about the current route is stored in the `\Pecee\SimpleRouter\Router` instance's `loadedRoute` property.

For easy access you can use the shortcut helper function `request()` instead of calling the class directly `\Pecee\SimpleRouter\SimpleRouter::router()`.


```php
request()->setRewriteCallback('Example\MyCustomClass@hello');

// -- or you can rewrite by url --

request()->setRewriteUrl('/my-rewrite-url');
```

### Bootmanager: loading routes dynamically

Sometimes it can be necessary to keep urls stored in the database, file or similar. In this example, we want the url ```/my-cat-is-beatiful``` to load the route ```/article/view/1``` which the router knows, because it's defined in the ```routes.php``` file.

To interfere with the router, we create a class that implements the ```IRouterBootManager``` interface. This class will be loaded before any other rules in ```routes.php``` and allow us to "change" the current route, if any of our criteria are fulfilled (like coming from the url ```/my-cat-is-beatiful```).

```php
use Pecee\Http\Request;
use Pecee\SimpleRouter\IRouterBootManager;
use Pecee\SimpleRouter\Router;

class CustomRouterRules implement IRouterBootManager 
{

    /**
     * Called when router is booting and before the routes is loaded.
     *
     * @param \Pecee\SimpleRouter\Router $router
     * @param \Pecee\Http\Request $request
     */
    public function boot(\Pecee\SimpleRouter\Router $router, \Pecee\Http\Request $request): void
    {

        $rewriteRules = [
            '/my-cat-is-beatiful' => '/article/view/1',
            '/horses-are-great'   => '/article/view/2',
        ];

        foreach($rewriteRules as $url => $rule) {

            // If the current url matches the rewrite url, we use our custom route

            if($request->getUrl()->contains($url)) {
                $request->setRewriteUrl($rule);
            }
        }

    }

}
```

The above should be pretty self-explanatory and can easily be changed to loop through urls store in the database, file or cache.

What happens is that if the current route matches the route defined in the index of our ```$rewriteRules``` array, we set the route to the array value instead.

By doing this the route will now load the url ```/article/view/1``` instead of ```/my-cat-is-beatiful```.

The last thing we need to do, is to add our custom boot-manager to the ```routes.php``` file. You can create as many bootmanagers as you like and easily add them in your ```routes.php``` file.

```php
SimpleRouter::addBootManager(new CustomRouterRules());
```

### Adding routes manually

The ```SimpleRouter``` class referenced in the previous example, is just a simple helper class that knows how to communicate with the ```Router``` class.
If you are up for a challenge, want the full control or simply just want to create your own ```Router``` helper class, this example is for you.

```php
use \Pecee\SimpleRouter\Router;
use \Pecee\SimpleRouter\Route\RouteUrl;

/* Create new Router instance */
$router = new Router();

$route = new RouteUrl('/answer/1', function() {

    die('this callback will match /answer/1');

});

$route->addMiddleware(\Demo\Middlewares\AuthMiddleware::class);
$route->setNamespace('\Demo\Controllers');
$route->setPrefix('v1');

/* Add the route to the router */
$router->addRoute($route);
```

## Custom class loader

You can easily extend simple-router to support custom injection frameworks like php-di by taking advantage of the ability to add your custom class-loader.

Class-loaders must inherit the `IClassLoader` interface.

**Example:**

```php
class MyCustomClassLoader implements IClassLoader
{
    /**
     * Load class
     *
     * @param string $class
     * @return object
     * @throws NotFoundHttpException
     */
    public function loadClass(string $class)
    {
        if (\class_exists($class) === false) {
            throw new NotFoundHttpException(sprintf('Class "%s" does not exist', $class), 404);
        }

        return new $class();
    }
    
    /**
     * Called when loading class method
     * @param object $class
     * @param string $method
     * @param array $parameters
     * @return object
     */
    public function loadClassMethod($class, string $method, array $parameters)
    {
        return call_user_func_array([$class, $method], array_values($parameters));
    }

    /**
     * Load closure
     *
     * @param Callable $closure
     * @param array $parameters
     * @return mixed
     */
    public function loadClosure(Callable $closure, array $parameters)
    {
        return \call_user_func_array($closure, array_values($parameters));
    }

}
```

Next, we need to configure our `routes.php` so the router uses our `MyCustomClassLoader` class for loading classes. This can be done by adding the following line to your `routes.php` file.

```php
SimpleRouter::setCustomClassLoader(new MyCustomClassLoader());
```

### Integrating with php-di

php-di support was discontinued by version 4.3, however you can easily add it again by creating your own class-loader like the example below:

```php
use Pecee\SimpleRouter\Exceptions\ClassNotFoundHttpException;

class MyCustomClassLoader implements IClassLoader
{

    protected $container;

    public function __construct()
    {
        // Create our new php-di container
        $this->container = (new \DI\ContainerBuilder())
                    ->useAutowiring(true)
                    ->build();
    }

    /**
     * Load class
     *
     * @param string $class
     * @return object
     * @throws NotFoundHttpException
     */
    public function loadClass(string $class)
    {
        if (class_exists($class) === false) {
            throw new NotFoundHttpException(sprintf('Class "%s" does not exist', $class), 404);
        }

		try {
			return $this->container->get($class);
		} catch (\Exception $e) {
			throw new NotFoundHttpException($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
		}
    }
    
    /**
     * Called when loading class method
     * @param object $class
     * @param string $method
     * @param array $parameters
     * @return object
     */
    public function loadClassMethod($class, string $method, array $parameters)
    {
		try {
			return $this->container->call([$class, $method], $parameters);
		} catch (\Exception $e) {
			throw new NotFoundHttpException($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
		}
    }

    /**
     * Load closure
     *
     * @param Callable $closure
     * @param array $parameters
     * @return mixed
     */
    public function loadClosure(callable $closure, array $parameters)
    {
		try {
			return $this->container->call($closure, $parameters);
		} catch (\Exception $e) {
			throw new NotFoundHttpException($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
		}
    }
}
```

## Parameters

This section contains advanced tips & tricks on extending the usage for parameters.

## Extending

This is a simple example of an integration into a framework.

The framework has it's own ```Router``` class which inherits from the ```SimpleRouter``` class. This allows the framework to add custom functionality like loading a custom `routes.php` file or add debugging information etc.

```php
namespace Demo;

use Pecee\SimpleRouter\SimpleRouter;

class Router extends SimpleRouter {

    public static function start() {

        // change this to whatever makes sense in your project
        require_once 'routes.php';

        // change default namespace for all routes
        parent::setDefaultNamespace('\Demo\Controllers');

        // Do initial stuff
        parent::start();

    }

}
```

---

# Help and support

This section will go into details on how to debug the router and answer some of the commonly asked questions- and issues.

## Common issues and fixes

This section will go over common issues and how to resolve them.

### Parameters won't match or route not working with special characters

Often people experience this issue when one or more parameters contains special characters. The router uses a sparse regular-expression that matches letters from a-z along with numbers when matching parameters, to improve performance. 

All other characters has to be defined via the `defaultParameterRegex` option on your route.

You can read more about adding your own custom regular expression for matching parameters by [clicking here](#custom-regex-for-matching-parameters).

### Multiple routes matches? Which one has the priority?

The router will match routes in the order they're added and will render multiple routes, if they match.

If you want the router to stop when a route is matched, you simply return a value in your callback or stop the execution manually (using `response()->json()` etc.) or simply by returning a result.

Any returned objects that implements the `__toString()` magic method will also prevent other routes from being rendered. 

If you want the router only to execute one route per request, you can [disabling multiple route rendering](#disable-multiple-route-rendering).

### Using the router on sub-paths

Please refer to [Setting custom base path](#setting-custom-base-path) part of the documentation.

## Debugging

This section will show you how to write unit-tests for the router, view useful debugging information and answer some of the frequently asked questions. 

It will also covers how to report any issue you might encounter. 

### Creating unit-tests

The easiest and fastest way to debug any issues with the router, is to create a unit-test that represents the issue you are experiencing.

Unit-tests use a special `TestRouter` class, which simulates a request-method and requested url of a browser.

The `TestRouter` class can return the output directly or render a route silently.

```php
public function testUnicodeCharacters()
{
    // Add route containing two optional paramters with special spanish characters like "í".
    TestRouter::get('/cursos/listado/{listado?}/{category?}', 'DummyController@method1', ['defaultParameterRegex' => '[\w\p{L}\s-]+']);
    
    // Start the routing and simulate the url "/cursos/listado/especialidad/cirugía local".
    TestRouter::debugNoReset('/cursos/listado/especialidad/cirugía local', 'GET');
    
    // Verify that the url for the loaded route matches the expected route.
    $this->assertEquals('/cursos/listado/{listado?}/{category?}/', TestRouter::router()->getRequest()->getLoadedRoute()->getUrl());
    
    // Start the routing and simulate the url "/test/Dermatología" using "GET" as request-method.
    TestRouter::debugNoReset('/test/Dermatología', 'GET');

    // Another route containing one parameter with special spanish characters like "í".
    TestRouter::get('/test/{param}', 'DummyController@method1', ['defaultParameterRegex' => '[\w\p{L}\s-\í]+']);

    // Get all parameters parsed by the loaded route.
    $parameters = TestRouter::request()->getLoadedRoute()->getParameters();

    // Check that the parameter named "param" matches the exspected value.
    $this->assertEquals('Dermatología', $parameters['param']);

    // Add route testing danish special characters like "ø".
    TestRouter::get('/category/økse', 'DummyController@method1', ['defaultParameterRegex' => '[\w\ø]+']);
    
    // Start the routing and simulate the url "/kategory/økse" using "GET" as request-method.
    TestRouter::debugNoReset('/category/økse', 'GET');
    
    // Validate that the URL of the loaded-route matches the expected url.
    $this->assertEquals('/category/økse/', TestRouter::router()->getRequest()->getLoadedRoute()->getUrl());

    // Reset the router, so other tests wont inherit settings or the routes we've added.
    TestRouter::router()->reset();
}
```

#### Using the TestRouter helper

Depending on your test, you can use the methods below when rendering routes in your unit-tests.


| Method        | Description  |
| ------------- |-------------|
| ```TestRouter::debug($url, $method)``` | Will render the route without returning anything. Exceptions will be thrown and the router will be reset automatically. |
| ```TestRouter::debugOutput($url, $method)``` | Will render the route and return any value that the route might output. Manual reset required by calling `TestRouter::router()->reset()`. |
| ```TestRouter::debugNoReset($url, $method);```  | Will render the route without resetting the router. Useful if you need to get loaded route, parameters etc. from the router. Manual reset required by calling `TestRouter::router()->reset()`. |

### Debug information

The library can output debug-information, which contains information like loaded routes, the parsed request-url etc. It also contains info which are important when reporting a new issue like PHP-version, library version, server-variables, router debug log etc.

You can activate the debug-information by calling the alternative start-method. 

The example below will start the routing an return array with debugging-information

**Example:**

```php
$debugInfo = SimpleRouter::startDebug();
echo sprintf('<pre>%s</pre>', var_export($debugInfo));
exit;
```

**The example above will provide you with an output containing:**

| Key               | Description  |
| -------------     |------------- |
| `url`             | The parsed request-uri. This url should match the url in the browser.|
| `method`          | The browsers request method (example: `GET`, `POST`, `PUT`, `PATCH`, `DELETE` etc).|
| `host`            | The website host (example: `domain.com`).|
| `loaded_routes`   | List of all the routes that matched the `url` and that has been rendered/loaded. |
| `all_routes`      | All available routes |
| `boot_managers`   | All available BootManagers |
| `csrf_verifier`   | CsrfVerifier class |
| `log`             | List of debug messages/log from the router. |
| `router_output`   | The rendered callback output from the router. |
| `library_version` | The version of simple-php-router you are using. |
| `php_version`     | The version of PHP you are using. |
| `server_params`   | List of all `$_SERVER` variables/headers. | 

#### Benchmark and logging

You can activate benchmark debugging/logging by calling `setDebugEnabled` method on the `Router` instance.

You have to enable debugging BEFORE starting the routing.

**Example:**

```php
SimpleRouter::router()->setDebugEnabled(true);
SimpleRouter::start();
```

When the routing is complete, you can get the debug-log by calling the `getDebugLog()` on the `Router` instance. This will return an `array` of log-messages each containing execution time, trace info and debug-message.

**Example:**

```php
$messages = SimpleRouter::router()->getDebugLog();
```

## Reporting a new issue

**Before reporting your issue, make sure that the issue you are experiencing aren't already answered in the [Common errors](#common-errors) section or by searching the [closed issues](https://github.com/skipperbent/simple-php-router/issues?q=is%3Aissue+is%3Aclosed) page on GitHub.**

To avoid confusion and to help you resolve your issue as quickly as possible, you should provide a detailed explanation of the problem you are experiencing.

### Procedure for reporting a new issue

1. Go to [this page](https://github.com/skipperbent/simple-php-router/issues/new) to create a new issue.
2. Add a title that describes your problems in as few words as possible.
3. Copy and paste the template below in the description of your issue and replace each step with your own information. If the step is not relevant for your issue you can delete it.

### Issue template

Copy and paste the template below into the description of your new issue and replace it with your own information.

You can check the [Debug information](#debug-information) section to see how to generate the debug-info.

<pre>
### Description

The library fails to render the route `/user/æsel` which contains one parameter using a custom regular expression for matching special foreign characters. Routes without special characters like `/user/tom` renders correctly.

### Steps to reproduce the error

1. Add the following route:

```php
SimpleRouter::get('/user/{name}', 'UserController@show')->where(['name' => '[\w]+']);
```

2. Navigate to `/user/æsel` in browser.

3. `NotFoundHttpException` is thrown by library.

### Route and/or callback for failing route

*Route:*

```php
SimpleRouter::get('/user/{name}', 'UserController@show')->where(['name' => '[\w]+']);
```

*Callback:*

```php
public function show($username) {
    return sprintf('Username is: %s', $username);
}
```

### Debug info

```php

[PASTE YOUR DEBUG-INFO HERE]

```
</pre>

Remember that a more detailed issue- description and debug-info might suck to write, but it will help others understand- and resolve your issue without asking for the information.

**Note:** please be as detailed as possible in the description when creating a new issue. This will help others to more easily understand- and solve your issue. Providing the necessary steps to reproduce the error within your description, adding useful debugging info etc. will help others quickly resolve the issue you are reporting.

## Feedback and development

If the library is missing a feature that you need in your project or if you have feedback, we'd love to hear from you. 
Feel free to leave us feedback by [creating a new issue](https://github.com/skipperbent/simple-php-router/issues/new).

**Experiencing an issue?**

Please refer to our [Help and support](#help-and-support) section in the documentation before reporting a new issue.

### Contribution development guidelines

- Please try to follow the PSR-2 codestyle guidelines.

- Please create your pull requests to the development base that matches the version number you want to change.
For example when pushing changes to version 3, the pull request should use the `v3-development` base/branch.

- Create detailed descriptions for your commits, as these will be used in the changelog for new releases.

- When changing existing functionality, please ensure that the unit-tests working.

- When adding new stuff, please remember to add new unit-tests for the functionality.

---

# Credits

## Sites

This is some sites that uses the simple-router project in production.

- [holla.dk](http://www.holla.dk)
- [ninjaimg.com](http://ninjaimg.com)
- [bookandbegin.com](https://bookandbegin.com)
- [dscuz.com](https://www.dscuz.com)

## License

### The MIT License (MIT)

Copyright (c) 2016 Simon Sessingø / simple-php-router

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

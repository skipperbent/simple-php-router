# Simple PHP router
Simple, fast and yet powerful PHP router that is easy to get integrated and in any project. Heavily inspired by the way Laravel handles routing, with both simplicity and expandability in mind.

**Note: this documentation is currently work-in-progress. Feel free to contribute.**

### Notes

The goal of this project is to create a router that is more or less 100% compatible with the Laravel documentation, while remaining as simple as possible, and as easy to integrate and change without compromising either speed or complexity. Being lightweight is the #1 priority.

### Ideas and issues

If you want a great new feature or experience any issues what-so-ever, please feel free to leave an issue and i'll look into it whenever possible.

---

## Table of Contents

- Gettings started
	- Requirements
	- Notes
	- Features
	- Installation
		- Setting up Apache
		- Setting up Nginx
		- Setting up simple-php-router
		- Helper methods

- Routes
	- Basic routing
		- Available methods
		- Multiple HTTP-verbs
	- Route parameters
		- Required parameters
		- Optional parameters
		- Regular expression constraints
		- Regular expression route-match
	- Named routes
		- Generating URLs To Named Routes
	- Router groups
		- Middleware
		- Namespaces
		- Subdomain-routing
		- Route prefixes
	- Form Method Spoofing
	- Accessing The Current Route
	- Other examples

- CSRF-protection
	- Adding CSRF-verifier
	- Getting CSRF-token

- Middleware
	- Example
- ExceptionHandler
	- Example

- Urls
 	- Get by name (single route)
 	- Get by name (controller route)
 	- Get by class
 	- Get by custom names for methods on a controller/resource route
 	- Getting REST/resource controller urls

- Input & parameters
	- Return single parameter value
	- Return single parameter object
	- Managing files
	- Return all parameters

- Advanced
	- Bootmanager: loading routes dynamically
	- Ovewrite route about to be loaded
		- Examples
		- Rewriting to new route
		- Changing callback

	- Adding routes manually
	- Extending

- Credits
	- Sites
	- License

___

# Getting started
Add the latest version of Simple PHP Router running this command.

```
composer require pecee/simple-router
```

## Requirements

- PHP 5.5 or greater

## Notes

We've included a simple demo project for the router which can be found in the `demo-project` folder. This project should give you a basic understanding of how to setup and use simple-php-router project.

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

### Setting up simple-php-router

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

We recommend that you add these helper functions to your project. Theese will allow you to access functionality of the router more easily.

To implement the functions below, simply copy the code to a new file and require the file before initializing the router.

```php
<?php
use Pecee\SimpleRouter\SimpleRouter;

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
 * @return string
 */
function url($name = null, $parameters = null, $getParams = null) {
    SimpleRouter::getUrl($name, $parameters, $getParams);
}

/**
 * Get current csrf-token
 * @return null|string
 */
function csrf_token() {
    $token = new \Pecee\CsrfToken();
    return $token->getToken();
}

/**
 * Get request object
 * @return \Pecee\Http\Request
 */
function request() {
    return SimpleRouter::request();
}

/**
 * Get response object
 * @return \Pecee\Http\Response
 */
function response() {
    return SimpleRouter::response();
}

/**
 * Get input class
 * @return \Pecee\Http\Input\Input
 */
function input() {
    return SimpleRouter::request()->getInput();
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

We've created a simple method which matches `GET` and `POST` which is most commenly used:

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

**Note:** Route parameters are always encased within {} braces and should consist of alphabetic characters. Route parameters may not contain a - character. Use an underscore (_) instead.

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

### Regular expression constraints

You may constrain the format of your route parameters using the where method on a route instance. The where method accepts the name of the parameter and a regular expression defining how the parameter should be constrained:

```php
SimpleRouter::get('/user/{name}', function ($name) {
    //
})->where('name', '[A-Za-z]+');

SimpleRouter::get('/user/{id}', function ($id) {
    //
})->where('id', '[0-9]+');

SimpleRouter::get('/user/{id}/{name}', function ($id, $name) {
    //
})->where(['id' => '[0-9]+', 'name' => '[a-z]+']);
```

### Regular expression route-match

You can define a regular-expression match for the entire route if you wish.

This is useful if you for example are creating a model-box which loads urls from ajax.

The example below is using the following regular expression: `/ajax/([\w]+)/?([0-9]+)?/?` which basically just matches `/ajax/` and exspects the next parameter to be a string - and the next to be a number (but optional).

**Matches:** `/ajax/abc/`, `/ajax/abc/123/`

**Doesn't match:** `/ajax/`

Match groups specified in the regex will be passed on as parameters:

```php
SimpleRouter::all('/ajax/abc/123', function($param1, $param2) {
	// param1 = abc
	// param2 = 123
})->setMatch('/\/ajax\/([\w]+)\/?([0-9]+)?\/?/is');
```

## Named routes

Named routes allow the convenient generation of URLs or redirects for specific routes. You may specify a name for a route by chaining the name method onto the route definition:

```php
SimpleRouter::get('/user/profile', function () {
    //
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
SimpleRouter::group(['middleware' => '\Demo\Middleware\Auth'], function () {
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

```php
SimpleRouter::group(['namespace' => 'Admin'], function () {
    // Controllers Within The "App\Http\Controllers\Admin" Namespace
});
```

### Subdomain-routing

Route groups may also be used to handle sub-domain routing. Sub-domains may be assigned route parameters just like route URIs, allowing you to capture a portion of the sub-domain for usage in your route or controller. The sub-domain may be specified using the `domain` key on the group attribute array:

```php
SimpleRouter::group(['domain' => '{account}.myapp.com'], function () {
    SimpleRouter::get('/user/{id}', function ($account, $id) {
        //
    });
});
```

### Route prefixes

The `prefix` group attribute may be used to prefix each route in the group with a given URI. For example, you may want to prefix all route URIs within the group with `admin`:

```php
SimpleRouter::group(['prefix' => '/admin'], function () {
    SimpleRouter::get('/users', function ()    {
        // Matches The "/admin/users" URL
    });
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
SimpleRouter::router()->getLoadedRoute();
```

## Other examples

You can find many more examples in the `routes.php` example-file below:

```php
<?php
use Pecee\SimpleRouter\SimpleRouter;

/* Adding custom csrfVerifier here */
SimpleRouter::csrfVerifier(new \Demo\Middlewares\CsrfVerifier());

SimpleRouter::group(['middleware' => '\Demo\Middlewares\Site', 'exceptionHandler' => 'Handlers\CustomExceptionHandler'], function() {


    SimpleRouter::get('/answers/{id}', 'ControllerAnswers@show', ['where' => ['id' => '[0-9]+']]);


    /**
     * Restful resource (see IRestController interface for available methods)
     */

    SimpleRouter::resource('/rest', 'ControllerRessource');


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

    SimpleRouter::controller('/animals', 'ControllerAnimals');

});

SimpleRouter::get('/page/404', 'ControllerPage@notFound', ['as' => 'page.notfound']);

```

---

# CSRF Protection

Any forms posting to `POST`, `PUT` or `DELETE` routes should include the CSRF-token. We strongly recommend that you create your enable CSRF-verification on your site.

Create a new class and extend the ```BaseCsrfVerifier``` middleware class provided with simple-php-router.

Add the property ```except``` with an array of the urls to the routes you would like to exclude/whitelist from the CSRF validation. Using ```*``` at the end for the url will match the entire url.

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

## Adding CSRF-verifier

When you've created your CSRF verifier - you need to tell simple-php-router that it should use it. You can do this by adding the following line in your `routes.php` file:

```php
Router::csrfVerifier(new \Demo\Middlewares\CsrfVerifier());
```

## Getting CSRF-token

When posting to any of the urls that has CSRF-verification enabled, you need post your CSRF-token or else the request will get rejected.

You can get the CSRF-token by calling the helper method:

```php
csrf_token();
```

---

# Middlewares

Middlewares are classes that loads before the route is rendered. A middleware can be used to verify that a user is logged in - or to set parameters specific for the current request/route. Middlewares must implement the `IMiddleware` interface.

## Example

```php
namespace Demo\Middlewares;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Route\ILoadableRoute;

class CustomMiddleware implements Middleware {

    public function handle(Request $request, ILoadableRoute &$route) {

        $request->setUri(url('home'));

    }
}
```

---

# ExceptionHandler

ExceptionHandler are classes that handles all exceptions. ExceptionsHandlers must implement the `IExceptionHandler` interface.

## Example

Resource controllers can implement the `IRestController` interface, but is not required.

This is a basic example of an ExceptionHandler implementation (please see "[Easily overwrite route about to be loaded](#easily-overwrite-route-about-to-be-loaded)" for examples on how to change callback).

```php
namespace Demo\Handlers;

use Pecee\Handlers\IExceptionHandler;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\Route\ILoadableRoute;

class CustomExceptionHandler implements IExceptionHandler
{
	public function handleError(Request $request, ILoadableRoute &$route = null, \Exception $error)
	{

		/* You can use the exception handler to format errors depending on the request and type. */

		if (stripos($request->getUri(), '/api') !== false) {

			response()->json([
				'error' => $error->getMessage(),
				'code'  => $error->getCode(),
			]);

		}

		/* The router will throw the NotFoundHttpException on 404 */
		if($error instanceof NotFoundHttpException) {

			/*
			 * Render your own custom 404-view, rewrite the request to another route,
			 * or simply return the $request object to ignore the error and continue on rendering the route.
			 *
			 * The code below will make the router render our page.notfound route.
			 */

			$request->setUri(url('page.notfound'));
			return $request;

		}

		throw $error;

	}

}
```

---

# Urls

By default all controller and resource routes will use a simplified version of their url as name.

### Get routes using custom name (single route)

```php
SimpleRouter::get('/product-view/{id}', 'ProductsController@show', ['as' => 'product']);

url('product', ['id' => 22], ['category' => 'shoes']);
url('product', null, ['category' => 'shoes']);

# output
# /product-view/22/?category=shoes
# /product-view/?category=shoes
```

### Getting the url using the name (controller route)

```php
SimpleRouter::controller('/images', 'ImagesController', ['as' => 'picture']);

url('picture@getView', null, ['category' => 'shoes']);
url('picture', 'getView', ['category' => 'shoes']);
url('picture', 'view');

# output
# /images/view/?category=shows
# /images/view/?category=shows
# /images/view/
```

### Getting the url using class

```php
SimpleRouter::get('/product-view/{id}', 'ProductsController@show', ['as' => 'product']);
SimpleRouter::controller('/images', 'ImagesController');

url('ProductsController@show', ['id' => 22], ['category' => 'shoes']);
url('ImagesController@getImage', null, ['id' => 22]);

# output
# /product-view/22/?category=shoes
# /images/image/?id=22
```

### Using custom names for methods on a controller/resource route

```php
SimpleRouter::controller('gadgets', 'GadgetsController', ['names' => ['getIphoneInfo' => 'iphone']]);

url('gadgets.iphone');

# output
# /gadgets/iphoneinfo/
```

### Getting REST/resource controller urls

```php
SimpleRouter::resource('/phones', 'PhonesController');

url('phones');
url('phones.index');
url('phones.create');
url('phones.edit');

// etc..

# output
# /phones/
# /phones/create/
# /phones/edit/
```

### Return the current url

```php
url();
url(null, null, ['q' => 'cars']);

# output
# /CURRENT-URL/
# /CURRENT-URL/?q=cars
```

# Input & parameters

## Using the Input class to manage parameters

We've added the `Input` class to easy access and manage parameters from your Controller-classes.

**Return single parameter value (matches both GET, POST, FILE):**

If items is grouped in the html, it will return an array of items.

**Note:** `get` will automatically trim the value and ensure that it's not empty. If it's empty the `$defaultValue` will be returned.

```php
$value = input()->get($index, $defaultValue, $methods);
```

**Return parameter object (matches both GET, POST, FILE):**

Will return an instance of `InputItem` or `InputFile` depending on the type.

You can use this in your html as it will render the value of the item.
However if you want to compare value in your if statements, you have to use
the `getValue` or use the `input()->get()` instead.

If items is grouped in the html, it will return an array of items.

**Note:** `getObject` will only return `$defaultValue` if the item doesn't exist. If you want `$defaultValue` to be returned if the item is empty, please use `input()->get()` instead.

```php
$object = input()->getObject($index, $defaultValue = null, $methods = null);
```

**Return specific GET parameter (where name is the name of your parameter):**

```php
# -- match any (default) --

/*
 * This is the recommended way to go for normal usage
 * as it will strip empty values, ensuring that
 * $defaultValue is returned if the value is empty.
 */

$id = input()->get($index, $defaultValue);

# -- match specific --

$object = input()->get($index, $defaultValue, 'get');
$object = input()->get($index, $defaultValue, 'post');
$object = input()->get($index, $defaultValue, 'file');

# -- or --

$object = input()->findGet($index, $defaultValue);
$object = input()->findPost($index, $defaultValue);
$object = input()->findFile($index, $defaultValue);

# -- examples --

/**
 * In this small example we loop through a collection of files
 * added on the page like this
 * <input type="file" name="images[]" />
 */

/* @var $image \Pecee\Http\Input\InputFile */
foreach(input()->get('images', []) as $image)
{
    if($image->getMime() === 'image/jpeg') {

        $destinationFilname = sprintf('%s.%s', uniqid(), $image->getExtension());

        $image->move('/uploads/' . $destinationFilename);

    }
}
```

**Return all parameters:**

```php
// Get all
$values = input()->all();

// Only match certain keys
$values = input()->all([
    'company_name',
    'user_id'
]);
```

All object implements the `IInputItem` interface and will always contain these methods:

- `getIndex()` - returns the index/key of the input.
- `getName()` - returns a human friendly name for the input (company_name will be Company Name etc).
- `getValue()` - returns the value of the input.

`InputFile` has the same methods as above along with some other file-specific methods like:
- `getTmpName()` - get file temporary name.
- `getSize()` - get file size.
- `move($destination)` - move file to destination.
- `getContents()` - get file content.
- `getType()` - get mime-type for file.
- `getError()` - get file upload error.
- `hasError()` - returns `bool` if an error occurred while uploading (if getError is not 0).
- `toArray()` - returns raw array

Below example requires you to have the helper functions added. Please refer to the helper functions section in the documentation.

```php
/* Get parameter site_id or default-value 2 from either post-value or query-string */
$siteId = input()->get('site_id', 2, ['post', 'get']);
```

---

# Advanced

## Load routes dynamicially using the router bootmanager

Sometimes it can be necessary to keep urls stored in the database, file or similar. In this example, we want the url ```/my-cat-is-beatiful``` to load the route ```/article/view/1``` which the router knows, because it's defined in the ```routes.php``` file.

To interfere with the router, we create a class that implements the ```IRouterBootManager``` interface. This class will be loaded before any other rules in ```routes.php``` and allow us to "change" the current route, if any of our criteria are fulfilled (like coming from the url ```/my-cat-is-beatiful```).

```php
use Pecee\Http\Request;
use Pecee\SimpleRouter\IRouterBootManager;

class CustomRouterRules implement IRouterBootManager {

    public function boot(Request $request) {

        $rewriteRules = [
            '/my-cat-is-beatiful' => '/article/view/1',
            '/horses-are-great' => '/article/view/2'
        ];

        foreach($rewriteRules as $url => $rule) {

            // If the current uri matches the url, we use our custom route

            if($request->getUri() === $url) {
                $request->setUri($rule);
            }
        }

        return $request;
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

## Easily overwrite route about to be loaded
Sometimes it can be useful to manipulate the route about to be loaded.
simple-php-router allows you to easily change the route about to be executed.
All information about the current route is stored in the ```\Pecee\SimpleRouter\Router``` instance's `loadedRoute` property.

For easy access you can use the shortcut method `\Pecee\SimpleRouter\SimpleRouter::router()`.


```php
use Pecee\SimpleRouter;
$route = SimpleRouter::router()->getLoadedRoute();

$route->setCallback('Example\MyCustomClass@hello');

// -- or you can rewrite by doing --

$route->setClass('Example\MyCustomClass');
$route->setMethod('hello');
```

### Examples

It's only possible to change the route BEFORE the route has initially been loaded. If you want to redirect to another route, we highly recommend that you
modify the `IRoute` object from a `Middleware` or `ExceptionHandler`, like the examples below.

#### Rewriting to new route

The example below will cause the router to re-route the request with another url. We are using the `url()` helper function to get the uri to another route added in the `routes.php` file.

**NOTE: Use this method if you want to fully load another route using it's settings (request method etc).**


```php
namespace Demo\Middlewares;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Route\ILoadableRoute;

class CustomMiddleware implements Middleware {

    public function handle(Request $request, ILoadableRoute &$route) {

        $request->setUri(url('home'));

    }
}
```

#### Changing callback
You can also change the callback by modifying the `$route` parameter. This is perfect if you just want to display a view quickly - or change the callback depending
on some criteria's for the request.

The callback below will fire immediately after the `Middleware` or `ExceptionHandler` has been loaded, as they are loaded before the route is rendered.
If you wish to change the callback from outside, please have this in mind.

**NOTE: Use this method if you want to load another controller. No additional middlewares or rules will be loaded.**

```php
namespace Demo\Middlewares;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Route\ILoadableRoute;

class CustomMiddleware implements Middleware {

    public function handle(Request $request, ILoadableRoute &$route) {

        $route->callback('DefaultController@home');

    }

}
```

## Adding routes manually

The ```SimpleRouter``` class referenced in the previous example, is just a simple helper class that knows how to communicate with the ```Router``` class.
If you are up for a challenge, want the full control or simply just want to create your own ```Router``` helper class, this example is for you.

```php
use \Pecee\SimpleRouter\Router;
use \Pecee\SimpleRouter\Route\RouteUrl;

/* Grap the router instance */
$router = Router::getInstance();

$route = new RouteUrl('/answer/1', function() {

    die('this callback will match /answer/1');

});

$route->setMiddleware('\Demo\Middlewares\AuthMiddleware');
$route->setNamespace('\Demo\Controllers');
$route->setPrefix('v1');

/* Add the route to the router */
$router->addRoute($route);
```

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

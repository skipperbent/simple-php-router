# Simple PHP router
Simple, fast and yet powerful PHP router that is easy to get integrated and in any project. Heavily inspired by the way Laravel handles routing, with both simplicity and expandability in mind.

## Installation
Add the latest version of Simple PHP Router running this command.

```
composer require pecee/simple-router
```

## Requirements

- PHP 5.4 or greater

## Notes

The goal of this project is to create a router that is 100% compatible with the Laravel documentation, but as simple as possible and as easy to integrate and change as possible.

### Features

- Basic routing (`GET`, `POST`, `PUT`, `DELETE`) with support for custom multiple verbs.
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
- Custom boot managers to redirect urls to other routes
- Input manager; to manage `GET`, `POST` params.

## Installation and demo

We've included a simple demo project for the router which can be found in the `demo-project` folder.

Please refer to the demo-project documentation for further reading on how to setup and install simple-php-router:

[Link to demo documentation](demo-project/README.md)

## Initialising the router

In your ```index.php``` require your ```routes.php``` and call the ```routeRequest()``` method when all your custom routes has been loaded. This will trigger and do the actual routing of the requests.

This is an example of a basic ```index.php``` file:

```php
use \Pecee\SimpleRouter\SimpleRouter;

// Load external routes file
require_once 'routes.php';

/* 
 * The default namespace for route-callbacks, so we don't have to specify it each time.
 * Can be overwritten by using the namespace config option.
 */
 
SimpleRouter::setDefaultNamespace('MyWebsite\Controller');

// Start the routing
SimpleRouter::start();
```

## Adding routes
Remember the ```routes.php``` file you required in your ```index.php```? This file will contain all your custom rules for routing.
This router is heavily inspired by the Laravel 5.* router, so anything you find in the Laravel documentation should work here as well.

### Basic example

- ExceptionsHandlers must implement the `IExceptionHandler` interface.
- Middlewares must implement the `IMiddleware` interface.

```php
use Pecee\SimpleRouter\SimpleRouter;

/*
 * This route will match the url /v1/services/answers/1/

 * The middleware is just a class that renders before the
 * Controller or callback is loaded. This is useful for stopping
 * the request, for instance if a user is not authenticated.
 */

// Add CSRF support (if needed)
SimpleRouter::csrfVerifier(new \Pecee\Http\Middleware\BaseCsrfVerifier());

SimpleRouter::get('/page/404', 'ControllerPage@notFound', ['as' => 'page.notfound']);

SimpleRouter::group(['prefix' => '/v1', 'middleware' => '\MyWebsite\Middleware\SomeMiddlewareClass'], function() {

    SimpleRouter::group(['prefix' => '/services', 'exceptionHandler' => '\MyProject\Handler\CustomExceptionHandler'], function() {

        SimpleRouter::get('/answers/{id}', 'ControllerAnswers@show')->where(['id' => '[0-9]+');

        // Optional parameter
        SimpleRouter::get('/answers/{id?}', 'ControllerAnswers@show');

        /**
         * This example will route url when matching the regular expression to the method.
         * For example route: domain.com/ajax/music/world -> ControllerAjax@process (parameter: music/world)
         */
        SimpleRouter::all('/ajax', 'ControllerAjax@process')->match('.*?\\/ajax\\/([A-Za-z0-9\\/]+)');

        // Restful resource
        SimpleRouter::resource('/rest', 'ControllerRessource');

        // Load the entire controller (where url matches method names - getIndex(), postIndex() etc)
        SimpleRouter::controller('/controller', 'ControllerDefault');

        // Example of providing callback instead of Controller
        SimpleRouter::get('/something', function() {
            die('Callback example');
        });

    });
});
```

#### ExceptionHandler example

This is a basic example of an ExceptionHandler implementation:

```php
namespace Demo\Handlers;

use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

class CustomExceptionHandler implements IExceptionHandler {

    public function handleError( Request $request, RouterEntry $router = null, \Exception $error) {

        // If the error-code is 404; show another route which contains the page-not-found
        if($error->getCode() === 404) {
        
            // Throw your custom 404-page view
            // - or -
            // load another route with our 404 page
            // - or -
            // you can return the $request object to ignore the error and continue on rendering the route.
            
            return $request->setUri(url('page.notfound'));
        }

        // Output error as json if on api path.
        if(stripos($request->getUri(), '/api') !== false) {
            response()->json(['error' => $error->getMessage()]);
        }

        // Otherwise default exception will be thrown by the router.

    }

}
```

### Sub-domain routing

Route groups may also be used to route wildcard sub-domains. Sub-domains may be assigned route parameters just like route URIs, allowing you to capture a portion of the sub-domain for usage in your route or controller. The sub-domain may be specified using the ```domain``` key on the group attribute array:

```php
Route::group(['domain' => '{account}.myapp.com'], function () {
    Route::get('user/{id}', function ($account, $id) {
        //
    });
});
```

The prefix group array attribute may be used to prefix each route in the group with a given URI. For example, you may want to prefix all route URIs within the group with admin:

### Doing it the object oriented (hardcore) way

The ```SimpleRouter``` class referenced in the previous example, is just a simple helper class that knows how to communicate with the ```RouterBase``` class.
If you are up for a challenge, want the full control or simply just want to create your own ```Router``` helper class, this example is for you.

```php
use \Pecee\SimpleRouter\RouterBase;
use \Pecee\SimpleRouter\RouterRoute;

$router = RouterBase::getInstance();

$route = new RouterRoute('/answer/1', function() {
    die('this callback will match /answer/1');
});

$route->setMiddleware('\HSWebserviceV1\Middleware\AuthMiddleware');
$route->setNamespace('MyWebsite');
$route->setPrefix('v1');

// Add the route to the router
$router->addRoute($route);
```

This is a simple example of an integration into a framework.

The framework has it's own ```Router``` class which inherits from the ```SimpleRouter``` class. This allows the framework to add custom functionality.

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

## Helper functions

To simplify to use of simple-router functionality, we recommend you add these helper functions to your project.

```php
use Pecee\SimpleRouter\SimpleRouter;

function url($controller, $parameters = null, $getParams = null) {
    SimpleRouter::getRoute($controller, $parameters, $getParams);
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

## Getting urls

**In ```routes.php``` we have added this route:**

```php
SimpleRouter::get('/item/{id}', 'myController@show', ['as' => 'item']);
```

**In the template we then call:**

```php
url('item', ['id' => 22], ['category' => 'shoes']);
```

**Result url is:**

```php
/item/22/?category=shoes
```

## Custom CSRF verifier

Create a new class and extend the ```BaseCsrfVerifier``` middleware class provided with simple-php-router.

Add the property ```except``` with an array of the urls to the routes you would like to exclude from the CSRF validation. Using ```*``` at the end for the url will match the entire url.

Querystrings are ignored.

```php
use Pecee\Http\Middleware\BaseCsrfVerifier;

class CsrfVerifier extends BaseCsrfVerifier {

    protected $except = [
        '/companies/*', 
        '/api'
    ];

}
```

Register the new class in your ```routes.php```, custom ```Router``` class or wherever you register your routes.

```php
SimpleRouter::csrfVerifier(new \Demo\Middleware\CsrfVerifier());
```

## Using router bootmanager to make custom rewrite rules

Sometimes it can be necessary to keep urls stored in the database, file or similar. In this example, we want the url ```/my-cat-is-beatiful``` to load the route ```/article/view/1``` which the router knows, because it's defined in the ```routes.php``` file.

To interfere with the router, we create a class that inherits from ```RouterBootManager```. This class will be loaded before any other rules in ```routes.php``` and allow us to "change" the current route, if any of our criteria are fulfilled (like coming from the url ```/my-cat-is-beatiful```).

```php
use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterBootManager;

class CustomRouterRules extends RouterBootManager{

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

## Easily overwrite route about to be loaded
Sometimes it can be useful to manipulate the route about to be loaded. 
simple-php-router allows you to easily change the route about to be executed. 
All information about the current route is stored in the ```\Pecee\SimpleRouter\RouterBase``` instance's `loadedRoute` property. 

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
modify the `RouterEntry` object from a `Middleware` or `ExceptionHandler`, like the examples below.

#### Faking new route

The example below will cause the router to re-route the request with another url. We are using the `url()` helper function to get the uri to another route added in the `routes.php` file.
 
This does require the `$request` object to be returned, otherwise the `request` object will be ignored by the router.

Using the example below will NOT inherit the rules from the other route. This means that IF you are faking a route that is enabled in `post`.

**NOTE: Use this method if you want to fully load a route (middlewares, request-method etc. will be kept).**


```php
namespace demo\Middlewares;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

class CustomMiddleware implements Middleware {

    public function handle(Request $request, RouterEntry &$route = null) {
        return $request->setUri(url('home'));
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
namespace demo\Middlewares;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

class CustomMiddleware implements Middleware {

    public function handle(Request $request, RouterEntry &$route = null) { 
        $route->callback('DefaultController@home');
    }
    
}
```

## Using the Input class to manage parameters

We've added the `Input` class to easy access parameters from your Controller-classes.

**Return single parameter value (matches both GET, POST, FILE):**
```php
$value = input()->get('name');
```

**Return parameter object (matches both GET, POST, FILE):**
```php
$object = input()->getObject('name');
```

**Return specific GET parameter (where name is the name of your parameter):**
```php
$object = input()->get->name;
$object = input()->post->name;
$object = input()->file->name;
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

All object inherits from `InputItem` class and will always contain these methods:
- `getValue()` - returns the value of the input.
- `getIndex()` - returns the index/key of the input.
- `getName()` - returns a human friendly name for the input (company_name will be Company Name etc).

`InputFile` has the same methods as above along with some other file-specific methods like:
- `getTmpName()` - get file temporary name.
- `getSize()` - get file size.
- `move($destination)` - move file to destination.
- `getContents()` - get file content.
- `getType()` - get mime-type for file.
- `getError()` - get file upload error.


### Easy access to methods

Below example requires you to have the helper functions added. Please refer to the helper functions section in the documentation.

```php
// Get parameter site_id or default-value 2
$siteId = input()->get('site_id', 2);
```

## Sites
This is some sites that uses the simple-router project in production.

- [holla.dk](http://www.holla.dk)
- [ninjaimg.com](http://ninjaimg.com)
- [bookandbegin.com](https://bookandbegin.com)
- [dscuz.com](https://www.dscuz.com)

## Documentation
While I work on a better documentation, please refer to the Laravel 5 routing documentation here:

http://laravel.com/docs/5.1/routing

## Easily extendable
The router can be easily extended to customize your needs.

## Ideas and issues
If you want a great new feature or experience any issues what-so-ever, please feel free to leave an issue and i'll look into it whenever possible.

## The MIT License (MIT)

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

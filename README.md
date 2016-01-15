# Simple PHP router
Simple, fast and yet powerful PHP router that is easy to get integrated and in any project. Heavily inspired by the way Laravel handles routing.

## Installation
Add the latest version of Simple PHP Router to your ```composer.json```

```json
{
    "require": {
        "pecee/simple-php-router": "1.*"
    },
    "require-dev": {
        "pecee/simple-php-router": "1.*"
    }
}
```

## Notes

The goal of this project is to create a router that is 100% compatible with the Laravel documentation, but as simple as possible and as easy to integrate and change as possible.

### Features

- Basic routing (get, post, put, delete) with support for custom multiple verbs.
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

### Features currently "in-the-works"

- Global Constraints

## Initialising the router

In your ```index.php``` require your ```routes.php``` and call the ```routeRequest()``` method when all your custom routes has been loaded. This will trigger and do the actual routing of the requests.

This is an example of a basic ```index.php``` file:

```php
use \Pecee\SimpleRouter;

require_once 'routes.php'; // change this to whatever makes sense in your project

// The apps default namespace (so we don't have to specify it each time we use MyController@home)
$defaultControllerNamespace = 'MyWebsite\\Controller';

// Do the routing
SimpleRouter::init($defaultControllerNamespace);
```

## Adding routes
Remember the ```routes.php``` file you required in your ```index.php```? This file will contain all your custom rules for routing.
This router is heavily inspired by the Laravel 5.* router, so anything you find in the Laravel documentation should work here as well.

### Basic example

```php
use Pecee\SimpleRouter\SimpleRouter;

/*
 * This route will match the url /v1/services/answers/1/

 * The middleware is just a class that renders before the
 * Controller or callback is loaded. This is useful for stopping
 * the request, for instance if a user is not authenticated.
 */

SimpleRouter::group(['prefix' => 'v1', 'middleware' => '\MyWebsite\Middleware\SomeMiddlewareClass'], function() {

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
namespace MyProject;

use Pecee\Handler\ExceptionHandler;
use Pecee\SimpleRouter\RouterBase;
use Pecee\SimpleRouter\SimpleRouter;

class Router extends SimpleRouter {

    protected static $defaultExceptionHandler;

    public static function start($defaultNamespace = null) {

        // Load routes.php
        $file = $_ENV['base_path'] . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'routes.php';
        if(file_exists($file)) {
            require_once $file;
        }

        // Set default namespace
        $defaultNamespace = '\\'.$_ENV['app_name'] . '\\Controller';

        // Handle exceptions
        try {
            parent::start($defaultNamespace);
        } catch(\Exception $e) {

            $route = RouterBase::getInstance()->getLoadedRoute();

            $exceptionHandler = null;

            // Load and use exception-handler defined on group
            
            if($route && $route->getGroup()) {
                $exceptionHandler = $route->getGroup()->getExceptionHandler();
                
                if($exceptionHandler !== null) {
                    $class = new $exceptionHandler();
                    $class->handleError(RouterBase::getInstance()->getRequest(), $route, $e);
                }
            }

            // Otherwise use the fallback default exceptions handler
           
            if(self::$defaultExceptionHandler !== null) {
                $class = new self::$defaultExceptionHandler();
                $class->handleError(RouterBase::getInstance()->getRequest(), $route, $e);
            }

            throw $e;
        }
    }

    public static function setDefaultExceptionHandler($handler) {
        self::$defaultExceptionHandler = $handler;
    }

}
```

#### Helper functions examples
**This is a basic example of a helper function for generating urls.**

```php
use Pecee\SimpleRouter\RouterBase;
function url($controller, $parameters = null, $getParams = null) {
    RouterBase::getInstance()->getRoute($controller, $parameters, $getParams);
}
```

**This is a basic example for getting the current csrf token**

```php
/**
 * Get current csrf-token
 * @return null|string
 */
function csrf_token() {
    $token = new \Pecee\CsrfToken();
    return $token->getToken();
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

    protected $except = ['/companies/*', '/user/save'];

}
```

Register the new class in your ```routes.php```, custom ```Router``` class or wherever you register your routes.

```php
SimpleRouter::csrfVerifier(new \Demo\Middleware\CsrfVerifier());
```

## Easily overwrite route about to be loaded
Sometimes it can be useful to manipulate the route that's about to be loaded, for instance if a user is not authenticated or if an error occurred within your Middleware that requires 
some other route to be initialised. Simple PHP Router allows you to easily change the route about to be executed. All information about the current route is stored in 
the ```\Pecee\SimpleRouter\Http\Request``` object.

**Note:** Please note that it's only possible to change the route BEFORE any route has initially been loaded, so doing this in your custom ExceptionHandler or Middleware is highly recommended.

```php
$route = Request::getInstance()->getLoadedRoute();

$route->setCallback('Example\MyCustomClass@hello');

// -- or --

$route->setClass('Example\MyCustomClass');
$route->setMethod('hello');
```

## Documentation
While I work on a better documentation, please refer to the Laravel 5 routing documentation here:

http://laravel.com/docs/5.1/routing

## Easily extendable
The router can be easily extended to customize your needs.

## Ideas and issues
If you want a great new feature or experience any issues what-so-ever, please feel free to leave an issue and i'll look into it whenever possible. 

## The MIT License (MIT)

Copyright (c) 2015 Simon Sessingø / simple-php-router

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

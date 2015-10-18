# Simple PHP router
Simple, fast PHP router that is easy to get integrated and in almost any project. Heavily inspired by the Laravel router.

## Installation
Add the latest version pf Simple PHP Router to your ```composer.json```

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

### Features currently "in-the-works"

- Global Constraints
- Named Routes
- Sub-Domain Routing
- CSRF Protection
- Optinal/required parameters

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

    SimpleRouter::group(['prefix' => 'services'], function() {

        SimpleRouter::get('/answers/{id}', 'ControllerAnswers@show')
        ->where(['id' => '[0-9]+');
        
        /**
         * This example will route url when matching the regular expression to the method.
         * For example route: /ajax/music/world -> ControllerAjax@process (parameter: music/world)
         */
        SimpleRouter::all('/ajax', 'ControllerAjax@process')->match('ajax\\/([A-Za-z0-9\\/]+)');
        
        // Resetful ressource
        SimpleRouter::ressource('/rest', 'ControllerRessource');
        
        // Load the entire controller (where url matches method names - getIndex(), postIndex() etc)
        SimpleRouter::controller('/controller', 'ControllerDefault');
        
        // Example of providing callback instead of Controller
        SimpleRouter::get('/something', function() {
            die('Callback example');
        });

    });
});
```

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
use Pecee\SimpleRouter\SimpleRouter;

class Router extends SimpleRouter {

    protected static $exceptionHandlers = array();

    public static function start() {

        Debug::getInstance()->add('Router initialised.');

        // Load routes.php
        $file = $_ENV['basePath'] . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'routes.php';
        if(file_exists($file)) {
            require_once $file;
        }

        // Init locale settings
        Locale::getInstance();

        // Set default namespace
        $defaultNamespace = '\\'.Registry::getInstance()->get('AppName') . '\\Controller';

        // Handle exceptions
        try {
            parent::start($defaultNamespace);
        } catch(\Exception $e) {
            /* @var $handler ExceptionHandler */
            foreach(self::$exceptionHandlers as $handler) {
                $class = new $handler();
                $class->handleError($e);
            }

            throw $e;
        }
    }

    public static function addExceptionHandler($handler) {
        self::$exceptionHandlers[] = $handler;
    }

}
```

This is a basic example of a helper function for generating urls.

```php
use Pecee\SimpleRouter\RouterBase;
function url($controller, $parameters = null, $getParams = null) {
    RouterBase::getInstance()->getRoute($controller, $parameters, $getParams);
}
```

In ```routes.php``` we have added this route:

```SimpleRouter::get('/item/{id}', 'myController@show');```

In the template we then call:

```url('myController@show', ['id' => 22], ['category' => 'shoes']);``` 

Result url is:

```/item/22?category=shoes ```

## Documentation
While I work on a better documentation, please refer to the Laravel 5 routing documentation here:

http://laravel.com/docs/5.1/routing

## Easily extendable
The router can be easily extended to customize your needs. 

## License
The MIT License (MIT)

Copyright (c) [year] [fullname]

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

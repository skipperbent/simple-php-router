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

## Initialising the router

In your ```index.php``` require your ```routes.php``` and call the ```routeRequest()``` method when all your custom routes has been loaded. This will trigger and do the actual routing of the requests.

This is an example of a basic ```index.php``` file:

```php
require_once 'routes.php'; // change this to whatever makes sense in your project
	
// Initialise the router
$router = \Pecee\SimpleRouter::GetInstance();
	
// Do the actual routing
$router->routeRequest()
```

## Adding routes
Remember the ```routes.php``` file you required in your ```index.php```? This file will contain all your custom rules for routing. 

This router is heavily inspired by the Laravel 5.* router, so anything you find in the Laravel documentation should work here as well.

### Basic example

```php
using \Pecee\Router;

/*
 * This route will match the url /v1/services/answers/1/
 
 * The middleware is just a class that renders before the 
 * Controller or callback is loaded. This is useful for stopping
 * the request, for instance if a user is not authenticated.
 */

Router::group(['prefix' => 'v1', 'middleware' => '\MyWebsite\Middleware\SomeMiddlewareClass'], function() {

    Router::group(['prefix' => 'services'], function() {

        Router::get('/answers/{id}', 'ControllerAnswers@show');

    });
});
```

### Doing it the object oriented (hardcore) way

The ```Router``` class is just a simple helper class that knows how to communicate with the ```SimpleRouter``` class. If you are up for a challenge, want the full control or simply just want to create your own ```Router``` helper class, this example is for you.

```php
use \Pecee\SimpleRouter;
use \Pecee\Router\RouterRoute;

$router = SimpleRouter::GetInstance();

$route = new RouterRoute('/answer/1', function() {
    die('this callback will match /answer/1');
});

$route->setMiddleware('\HSWebserviceV1\Middleware\AuthMiddleware');
$route->setNamespace('MyWebsite');
$route->setPrefix('v1');

// Add the route to the router
$router->addRoute($route);
```

## Documentation
While I work on a better documentation, please refer to the Laravel 5 routing documentation here:

http://laravel.com/docs/5.1/routing

## Easily extendable
The router can be easily extended to customize your needs. 

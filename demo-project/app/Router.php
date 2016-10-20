<?php

/**
 * Custom router which handles default middlewares, default exceptions and things
 * that should be happen before and after the router is initialised.
 */

namespace Demo;

use Pecee\Exception\RouterException;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\SimpleRouter\RouterBase;
use Pecee\SimpleRouter\SimpleRouter;

class Router extends SimpleRouter {

    protected static $defaultMiddlewares = array();

    public static function start($defaultNamespace = null) {

        // change this to whatever makes sense in your project
        require_once 'routes.php';


        if(count(static::$defaultMiddlewares)) {
            /* @var $middleware \Pecee\Http\Middleware\IMiddleware */
            foreach(static::$defaultMiddlewares as $middleware) {
                $middleware = new $middleware();
                if(!($middleware instanceof IMiddleware)) {
                    throw new RouterException('Middleware must be implement the IMiddleware interface.');
                }
                $middleware->handle(RouterBase::getInstance()->getRequest());
            }
        }

        // Set default namespace
        $defaultNamespace = '\\Demo\\Controllers';

        parent::start($defaultNamespace);

    }

    /**
     * Add default middleware that will be loaded before any route
     * @param string|array $middlewares
     */
    public static function defaultMiddleware($middlewares) {
        if(is_array($middlewares)) {
            static::$defaultMiddlewares = $middlewares;
        } else {
            static::$defaultMiddlewares[] = $middlewares;
        }
    }

}
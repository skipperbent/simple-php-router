<?php

/**
 * Custom router which handles default middlewares, default exceptions and things
 * that should be happen before and after the router is initialised.
 */

namespace Demo;

use Pecee\Exception\RouterException;
use Pecee\Handler\IExceptionHandler;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\SimpleRouter\RouterBase;
use Pecee\SimpleRouter\SimpleRouter;

class Router extends SimpleRouter {

    protected static $defaultExceptionHandler;
    protected static $defaultMiddlewares = array();

    public static function start($defaultNamespace = null) {

        // change this to whatever makes sense in your project
        require_once 'routes.php';

        // Handle exceptions
        try {

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
        } catch(\Exception $e) {

            $route = RouterBase::getInstance()->getLoadedRoute();

            // Otherwise use the fallback default exceptions handler
            if(static::$defaultExceptionHandler !== null) {
                static::loadExceptionHandler(static::$defaultExceptionHandler, $route, $e);
            }

            throw $e;
        }

    }

    protected static function loadExceptionHandler($class, $route, $e) {
        $class = new $class();

        if(!($class instanceof IExceptionHandler)) {
            throw new \ErrorException('Exception handler must be an instance of \Pecee\Handler\IExceptionHandler');
        }

        $class->handleError(RouterBase::getInstance()->getRequest(), $route, $e);
    }

    public static function defaultExceptionHandler($handler) {
        static::$defaultExceptionHandler = $handler;
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
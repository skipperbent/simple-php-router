<?php

/**
 * ---------------------------
 * Router helper class
 * ---------------------------
 * This class is added so calls can be made statically like Router::get() making the code look more pretty.
 */

namespace Pecee\SimpleRouter;

use Pecee\Http\Middleware\BaseCsrfVerifier;

class SimpleRouter {

    /**
     * Start/route request
     * @param null $defaultNamespace
     * @throws RouterException
     */
    public static function start($defaultNamespace = null) {
        $router = RouterBase::getInstance();
        $router->setDefaultNamespace($defaultNamespace);
        $router->routeRequest();
    }

    /**
     * Set base csrf verifier
     * @param BaseCsrfVerifier $baseCsrfVerifier
     */
    public static function csrfVerifier(BaseCsrfVerifier $baseCsrfVerifier) {
        RouterBase::getInstance()->setBaseCsrfVerifier($baseCsrfVerifier);
    }

    public static function get($url, $callback, array $settings = null) {
        $route = new RouterRoute($url, $callback);
        $route->addSettings($settings);
        $route->setRequestMethods(array(RouterRoute::REQUEST_TYPE_GET));

        $router = RouterBase::getInstance();
        $router->addRoute($route);

        return $route;
    }

    public static function post($url, $callback, array $settings = null) {
        $route = new RouterRoute($url, $callback);
        $route->addSettings($settings);
        $route->setRequestMethods(array(RouterRoute::REQUEST_TYPE_POST));

        $router = RouterBase::getInstance();
        $router->addRoute($route);

        return $route;
    }

    public static function put($url, $callback, array $settings = null) {
        $route = new RouterRoute($url, $callback);
        $route->addSettings($settings);
        $route->setRequestMethods(array(RouterRoute::REQUEST_TYPE_PUT, RouterRoute::REQUEST_TYPE_PATCH));

        $router = RouterBase::getInstance();
        $router->addRoute($route);

        return $route;
    }

    public static function delete($url, $callback, array $settings = null) {
        $route = new RouterRoute($url, $callback);
        $route->addSettings($settings);
        $route->setRequestMethods(array(RouterRoute::REQUEST_TYPE_DELETE));

        $router = RouterBase::getInstance();
        $router->addRoute($route);

        return $route;
    }

    public static function group($settings = array(), $callback) {
        $group = new RouterGroup();
        $group->setCallback($callback);

        if($settings !== null && is_array($settings)) {
            $group->setSettings($settings);
        }

        $router = RouterBase::getInstance();
        $router->addRoute($group);

        return $group;
    }

    public static function match(array $requestMethods, $url, $callback, array $settings = null) {
        $route = new RouterRoute($url, $callback);
        $route->setRequestMethods($requestMethods);
        $route->addSettings($settings);

        $router = RouterBase::getInstance();
        $router->addRoute($route);

        return $route;
    }

    public static function all($url, $callback, array $settings = null) {
        $route = new RouterRoute($url, $callback);
        $route->addSettings($settings);
        $router = RouterBase::getInstance();
        $router->addRoute($route);

        return $route;
    }

    public static function controller($url, $controller, array $settings = null) {
        $route = new RouterController($url, $controller);
        $route->addSettings($settings);
        $router = RouterBase::getInstance();
        $router->addRoute($route);

        return $route;
    }

    public static function resource($url, $controller, array $settings = null) {
        $route = new RouterResource($url, $controller);
        $route->addSettings($settings);
        $router = RouterBase::getInstance();
        $router->addRoute($route);

        return $route;
    }

    public static function getRoute($controller = null, $parameters = null, $getParams = null) {
        return RouterBase::getInstance()->getRoute($controller, $parameters, $getParams);
    }

}
<?php

/**
 * ---------------------------
 * Router helper class
 * ---------------------------
 * This class is added so calls can be made staticly like Router::get() making the code look more pretty.
 */

namespace Pecee\SimpleRouter;

use Pecee\Router\RouterGroup;
use Pecee\Router\RouterRoute;
use Pecee\Router\Router;

class SimpleRouter {

    public static function init($defaultNamespace = null) {
        $router = Router::GetInstance();
        $router->setDefaultControllerNamespace($defaultNamespace);
        $router->routeRequest();
    }

    public static function get($url, $callback) {
        $route = new RouterRoute($url, $callback);
        $route->addRequestType(RouterRoute::REQUEST_TYPE_GET);

        $router = Router::GetInstance();
        $router->addRoute($route);

        return $route;
    }

    public static function post($url, $callback) {
        $route = new RouterRoute($url, $callback);
        $route->addRequestType(RouterRoute::REQUEST_TYPE_POST);

        $router = Router::GetInstance();
        $router->addRoute($route);

        return $route;
    }

    public static function put($url, $callback) {
        $route = new RouterRoute($url, $callback);
        $route->addRequestType(RouterRoute::REQUEST_TYPE_PUT);

        $router = Router::GetInstance();
        $router->addRoute($route);

        return $route;
    }

    public static function delete($url, $callback) {
        $route = new RouterRoute($url, $callback);
        $route->addRequestType(RouterRoute::REQUEST_TYPE_DELETE);

        $router = Router::GetInstance();
        $router->addRoute($route);

        return $route;
    }

    public static function group($settings = array(), $callback) {
        $group = new RouterGroup();
        $group->setCallback($callback);

        if($settings !== null && is_array($settings)) {
            $group->setSettings($settings);
        }

        $router = Router::GetInstance();
        $router->addRoute($group);

        return $group;
    }

    public static function match(array $requestTypes, $url, $callback) {
        $route = new RouterRoute($url, $callback);
        foreach($requestTypes as $requestType) {
            $route->addRequestType($requestType);
        }

        $router = Router::GetInstance();
        $router->addRoute($route);

        return $route;
    }

}
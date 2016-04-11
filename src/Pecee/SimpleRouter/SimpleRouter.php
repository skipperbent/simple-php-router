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
     * @throws \Pecee\Exception\RouterException
     */
    public static function start($defaultNamespace = null) {
        RouterBase::getInstance()->setDefaultNamespace($defaultNamespace)->routeRequest();
    }

    /**
     * Set base csrf verifier
     * @param BaseCsrfVerifier $baseCsrfVerifier
     */
    public static function csrfVerifier(BaseCsrfVerifier $baseCsrfVerifier) {
        RouterBase::getInstance()->setBaseCsrfVerifier($baseCsrfVerifier);
    }

    public static function addBootManager(RouterBootManager $bootManager) {
        RouterBase::getInstance()->addBootManager($bootManager);
    }

    public static function get($url, $callback, array $settings = null) {
        return self::match(['get'], $url, $callback, $settings);
    }

    public static function post($url, $callback, array $settings = null) {
        return self::match(['post'], $url, $callback, $settings);
    }

    public static function put($url, $callback, array $settings = null) {
        return self::match(['put'], $url, $callback, $settings);
    }

    public static function delete($url, $callback, array $settings = null) {
        return self::match(['delete'], $url, $callback, $settings);
    }

    public static function group($settings = array(), $callback) {
        $group = new RouterGroup();
        $group->setCallback($callback);

        if($settings !== null && is_array($settings)) {
            $group->setSettings($settings);
        }

        RouterBase::getInstance()->addRoute($group);

        return $group;
    }

    /**
     * Adds get + post route
     *
     * @param string $url
     * @param callable $callback
     * @param array|null $settings
     * @return RouterRoute
     */
    public static function basic($url, $callback, array $settings = null) {
        return self::match(['get', 'post'], $url, $callback, $settings);
    }

    public static function match(array $requestMethods, $url, $callback, array $settings = null) {
        $route = new RouterRoute($url, $callback);
        $route->setRequestMethods($requestMethods);

        if($settings !== null) {
            $route->addSettings($settings);
        }

        RouterBase::getInstance()->addRoute($route);

        return $route;
    }

    public static function all($url, $callback, array $settings = null) {
        $route = new RouterRoute($url, $callback);

        if($settings !== null) {
            $route->addSettings($settings);
        }

        RouterBase::getInstance()->addRoute($route);

        return $route;
    }

    public static function controller($url, $controller, array $settings = null) {
        $route = new RouterController($url, $controller);

        if($settings !== null) {
            $route->addSettings($settings);
        }

        RouterBase::getInstance()->addRoute($route);

        return $route;
    }

    public static function resource($url, $controller, array $settings = null) {
        $route = new RouterResource($url, $controller);

        if($settings !== null) {
            $route->addSettings($settings);
        }

        RouterBase::getInstance()->addRoute($route);

        return $route;
    }

    public static function getRoute($controller = null, $parameters = null, $getParams = null) {
        return RouterBase::getInstance()->getRoute($controller, $parameters, $getParams);
    }

}
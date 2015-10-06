<?php
namespace Pecee\SimpleRouter;

use Pecee\Url;

class RouterBase {

    protected static $instance;

    protected $currentRoute;
    protected $routes;
    protected $processedRoutes;
    protected $controllerUrlMap;
    protected $backstack;
    protected $requestUri;
    protected $requestMethod;
    protected $loadedClass;
    protected $defaultNamespace;

    public function __construct() {
        $this->routes = array();
        $this->backstack = array();
        $this->controllerUrlMap = array();
        $this->requestUri = $_SERVER['REQUEST_URI'];
        $this->requestMethod = ($_SERVER['REQUEST_METHOD'] != 'GET') ? 'post' : 'get';
    }

    public function addRoute(RouterEntry $route) {
        if($this->currentRoute !== null) {
            $this->backstack[] = $route;
        } else {
            $this->routes[] = $route;
        }
    }

    protected function processRoutes(array $routes, array &$settings = array(), array &$prefixes = array(), $match = false, $backstack = false) {
        // Loop through each route-request

        /* @var $route RouterEntry */
        foreach($routes as $i => $route) {

            if($this->defaultNamespace) {
                $namespace = null;

                if ($route->getNamespace()) {
                    $namespace = $this->defaultNamespace . '\\' . $route->getNamespace();
                } else {
                    $namespace = $this->defaultNamespace;
                }

                $route->setNamespace($namespace);
            }

            $settings = array_merge($settings, $route->getMergeableSettings());
            if($route->getPrefix()) {
                array_push($prefixes, $route->getPrefix());
            }

            $route->setSettings($settings);

            if(($route instanceof RouterRoute || $route instanceof RouterController)) {
                if(is_array($prefixes) && count($prefixes)) {
                    $route->setUrl( '/' . join('/', $prefixes) . $route->getUrl() );
                }
            }

            $this->currentRoute = $route;

            if($route instanceof RouterRoute && !is_callable($route->getCallback()) && stripos($route->getCallback(), '@') !== false) {
                $this->controllerUrlMap[] = $route;
            } else if($route instanceof RouterController) {
                $this->controllerUrlMap[] = $route;
            }

            $routeMatch = $route->matchRoute($this->requestMethod, rtrim($this->requestUri, '/') . '/');

            if($routeMatch && $match) {
                $this->loadedClass = $routeMatch->renderRoute($this->requestMethod);
            }

            if(count($this->backstack)) {

                if($backstack) {
                    array_shift($this->backstack);
                }

                // Route any routes added to the backstack
                $this->processRoutes($this->backstack, $settings, $prefixes, $match, true);
            }

        }
    }

    public function routeRequest() {
        // Loop through each route-request
        $settings = array();
        $prefixes = array();

        $this->processRoutes($this->routes, $settings, $prefixes, true);

        if(!$this->loadedClass) {
            throw new RouterException(sprintf('Route not found: %s', $this->requestUri), 404);
        }
    }

    /**
     * @return string
     */
    public function getDefaultNamespace(){
        return $this->defaultNamespace;
    }

    /**
     * @param string $defaultNamespace
     */
    public function setDefaultNamespace($defaultNamespace) {
        $this->defaultNamespace = $defaultNamespace;
    }

    /**
     * @return RouterEntry
     */
    public function getLoadedClass() {
        return $this->loadedClass;
    }

    /**
     * @return string
     */
    public function getRequestMethod() {
        return $this->requestMethod;
    }

    /**
     * @return string
     */
    public function getRequestUri() {
        return $this->requestUri;
    }

    /**
     * @return array
     */
    public function getBackstack() {
        return $this->backstack;
    }

    /**
     * @return RouterEntry
     */
    public function getCurrentRoute(){
        if($this->currentRoute !== null && !($this->currentRoute instanceof RouterGroup)) {
            return $this->currentRoute;
        }
        return null;
    }

    /**
     * @return array
     */
    public function getRoutes(){
        return $this->routes;
    }

    protected function processUrl($route, $method = null, $parameters = null, $getParams = null) {
        $url = rtrim($route->getUrl(), '/') . '/';

        if($method !== null) {
            $url .= $method . '/';
        }

        if($route instanceof RouterController) {
            if(count($parameters)) {
                $url .= join('/', $parameters);
            }

        } else {
            $params = $route->getParameters();
            if(count($params)) {
                $i = 0;
                foreach($params as $param => $value) {
                    $value = (isset($parameters[$param])) ? $parameters[$param] : $value;
                    $url = str_ireplace('{' . $param. '}', $value, $route->getUrl());
                    $i++;
                }
            }
        }

        $p = '';
        if($getParams !== null) {
            $p = '?'.Url::arrayToParams($getParams);
        }

        $url .= $p;

        return $url;
    }

    public function getRoute($controller = null, $parameters = null, $getParams = null) {
        $c = '';
        $method = null;

        /* @var $route RouterRoute */
        foreach($this->controllerUrlMap as $route) {

            if($route instanceof RouterRoute && !is_callable($route->getCallback()) && stripos($route->getCallback(), '@') !== false) {
                $c = $route->getCallback();
            } else if($route instanceof RouterController) {
                $c = $route->getController();
            }

            if($c === $controller || strpos($c, $controller) === 0) {
                if(stripos($c, '@') !== false) {
                    $tmp = explode('@', $route->getCallback());
                    $method = strtolower($tmp[1]);
                }
                return $this->processUrl($route, $method, $parameters, $getParams);
            }
        }

        // No match has yet been found, let's try to guess what url that should be returned
        foreach($this->controllerUrlMap as $route) {
            if($route instanceof RouterRoute && !is_callable($route->getCallback()) && stripos($route->getCallback(), '@') !== false) {
                $c = $route->getCallback();

                if(stripos($controller, '@') !== false) {
                    $tmp = explode('@', $controller);
                    $c = $tmp[0];
                }

            } else if($route instanceof RouterController) {
                $c = $route->getController();
            }

            if(stripos($controller, '@') !== false) {
                $tmp = explode('@', $controller);
                $controller = $tmp[0];
                $method = $tmp[1];
            }

            if($controller == $c) {
                return $this->processUrl($route, $method, $parameters, $getParams);
            }
        }

        return '/';
    }

    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

}
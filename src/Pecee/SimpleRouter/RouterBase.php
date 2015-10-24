<?php
namespace Pecee\SimpleRouter;

use Pecee\ArrayUtil;
use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\Http\Request;
use Pecee\Url;

class RouterBase {

    protected static $instance;

    protected $request;
    protected $currentRoute;
    protected $routes;
    protected $processedRoutes;
    protected $controllerUrlMap;
    protected $backstack;
    protected $loadedRoute;
    protected $defaultNamespace;
    protected $baseCsrfVerifier;

    // TODO: make interface for controller routers, so they can be easily detected
    // TODO: clean up - cut some of the methods down to smaller pieces

    public function __construct() {
        $this->routes = array();
        $this->backstack = array();
        $this->controllerUrlMap = array();
        $this->request = new Request();
        $this->baseCsrfVerifier = new BaseCsrfVerifier();
    }

    public function addRoute(RouterEntry $route) {
        if($this->currentRoute !== null) {
            $this->backstack[] = $route;
        } else {
            $this->routes[] = $route;
        }
    }

    protected function processRoutes(array $routes, array $settings = array(), array $prefixes = array(), $backstack = false) {
        // Loop through each route-request

        /* @var $route RouterEntry */
        foreach($routes as $route) {

            if($this->defaultNamespace && !$route->getNamespace()) {
                $namespace = null;
                if ($route->getNamespace()) {
                    $namespace = $this->defaultNamespace . '\\' . $route->getNamespace();
                } else {
                    $namespace = $this->defaultNamespace;
                }

                $route->setNamespace($namespace);
            }

            $newPrefixes = $prefixes;
            $mergedSettings = array_merge($settings, $route->getMergeableSettings());
            if($route->getPrefix()) {
                array_push($newPrefixes, rtrim($route->getPrefix(), '/'));
            }
            $route->addSettings($mergedSettings);

            if(!($route instanceof RouterGroup)) {
                if(is_array($newPrefixes) && count($newPrefixes) && $backstack) {
                    $route->setUrl( join('/', $newPrefixes) . $route->getUrl() );
                }

                $this->controllerUrlMap[] = $route;
            }

            $this->currentRoute = $route;
            if($route instanceof RouterGroup && is_callable($route->getCallback())) {
                $route->renderRoute($this->request);
            }
            $this->currentRoute = null;

            if(count($this->backstack)) {
                $backstack = $this->backstack;
                $this->backstack = array();

                // Route any routes added to the backstack
                $this->processRoutes($backstack, $mergedSettings, $newPrefixes, true);
            }
        }
    }

    public function routeRequest() {

        // Verify csrf token for request
        if($this->baseCsrfVerifier !== null) {
            /* @var $csrfVerifier BaseCsrfVerifier */
            $csrfVerifier = $this->baseCsrfVerifier;
            $csrfVerifier = new $csrfVerifier();
            $csrfVerifier->handle($this->request);
        }

        // Loop through each route-request
        $this->processRoutes($this->routes);

        // Make sure the urls is in the right order when comparing
        usort($this->controllerUrlMap, function($a, $b) {
            return strcmp($b->getUrl(), $a->getUrl());
        });

        $routeNotAllowed = false;

        /* @var $route RouterEntry */
        foreach($this->controllerUrlMap as $route) {
            $routeMatch = $route->matchRoute($this->request);

            if($routeMatch && !($routeMatch instanceof RouterGroup)) {

                if(count($route->getRequestMethods()) && !in_array($this->request->getMethod(), $route->getRequestMethods())) {
                    $routeNotAllowed = true;
                    continue;
                }

                $routeNotAllowed = false;

                $this->loadedRoute = $routeMatch;
                $routeMatch->renderRoute($this->request);
                break;
            }
        }

        if($routeNotAllowed) {
            throw new RouterException('Route or method not allowed', 403);
        }

        if(!$this->loadedRoute) {
            throw new RouterException(sprintf('Route not found: %s', $this->request->getUri()), 404);
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
    public function getLoadedRoute() {
        if(!($this->loadedRoute instanceof RouterGroup)) {
            return $this->loadedRoute;
        }
        return null;
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
        return $this->currentRoute;
    }

    /**
     * @return array
     */
    public function getRoutes(){
        return $this->routes;
    }

    /**
     * Get current request
     *
     * @return Request
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Get base csrf verifier class
     * @return BaseCsrfVerifier
     */
    public function getBaseCsrfVerifier() {
        return $this->baseCsrfVerifier;
    }

    /**
     * Set base csrf verifier class
     *
     * @param BaseCsrfVerifier $baseCsrfVerifier
     * @return self
     */
    public function setBaseCsrfVerifier(BaseCsrfVerifier $baseCsrfVerifier) {
        $this->baseCsrfVerifier = $baseCsrfVerifier;
        return $this;
    }

    protected function processUrl($route, $method = null, $parameters = null, $getParams = null) {

        $url = '/' . trim($route->getUrl(), '/');

        if(($route instanceof RouterController || $route instanceof RouterResource) && $method !== null) {
            $url .= $method;
        }

        if($route instanceof RouterController || $route instanceof RouterResource) {
            if(count($parameters)) {
                $url .= join('/', $parameters);
            }
        } else {
            /* @var $route RouterEntry */
            $params = $route->getParameters();
            if(count($params)) {
                $i = 0;
                foreach($params as $param => $value) {
                    $value = (isset($parameters[$param])) ? $parameters[$param] : $value;
                    $url = str_ireplace('{' . $param. '}', $value, $url);
                    $i++;
                }
            } else {
                // If no parameters are specified in the route, assume that the provided parameters should be used.
                if(count($parameters)) {
                    $url = rtrim($url, '/') . '/' . join('/', $parameters);
                }
            }
        }

        $url = rtrim($url, '/') . '/';

        if($getParams !== null && count($getParams)) {
            $url .= '?'.Url::arrayToParams($getParams);
        }

        return $url;
    }

    public function getRoute($controller = null, $parameters = null, $getParams = null) {

        if($parameters !== null && !is_array($parameters)) {
            throw new \InvalidArgumentException('Invalid type for parameter. Must be array or null');
        }

        if($getParams !== null && !is_array($getParams)) {
            throw new \InvalidArgumentException('Invalid type for getParams. Must be array or null');
        }

        if($controller === null && $parameters === null) {
            return $this->processUrl($this->loadedRoute, null, $getParams);
        }

        $c = '';
        $method = null;

        /* @var $route RouterRoute */
        foreach($this->controllerUrlMap as $route) {

            // Check an alias exist, if the matches - use it
            if($route instanceof RouterRoute && strtolower($route->getAlias()) === strtolower($controller)) {
                return $this->processUrl($route, $route->getMethod(), $parameters, $getParams);
            }

            if($route instanceof RouterRoute && !is_callable($route->getCallback()) && stripos($route->getCallback(), '@') !== false) {
                $c = $route->getCallback();
            } else if($route instanceof RouterController || $route instanceof RouterResource) {
                $c = $route->getController();
            }

            if($c === $controller || strpos($c, $controller) === 0) {
                return $this->processUrl($route, $route->getMethod(), $parameters, $getParams);
            }
        }

        $c = '';

        // No match has yet been found, let's try to guess what url that should be returned
        foreach($this->controllerUrlMap as $route) {
            if($route instanceof RouterRoute && !is_callable($route->getCallback()) && stripos($route->getCallback(), '@') !== false) {
                $c = $route->getClass();
            } else if($route instanceof RouterController || $route instanceof RouterResource) {
                $c = $route->getController();
            }

            if(stripos($controller, '@') !== false) {
                $tmp = explode('@', $controller);
                $controller = $tmp[0];
                $method = $tmp[1];
            }

            if($controller === $c) {
                return $this->processUrl($route, $method, $parameters, $getParams);
            }
        }

        $controller = ($controller === null) ? '/' : $controller;
        $url = array($controller);

        if(is_array($parameters)) {
            ArrayUtil::append($url, $parameters);
        }

        return '/' . join('/', $url);
    }

    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

}
<?php
namespace Pecee\SimpleRouter;

use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\Http\Request;

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
        $this->request = Request::getInstance();
    }

    public function addRoute(RouterEntry $route) {
        if($this->currentRoute !== null) {
            $this->backstack[] = $route;
        } else {
            $this->routes[] = $route;
        }
    }

    protected function processRoutes(array $routes, array $settings = array(), array $prefixes = array(), $backStack = false, $group = null) {
        // Loop through each route-request

        $activeGroup = null;
        $routesCount = count($routes);
        $mergedSettings = array();

        /* @var $route RouterEntry */
        for($i = 0; $i < $routesCount; $i++) {

            $route = $routes[$i];

            $route->setGroup($group);

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

            if($route->getPrefix()) {
                array_push($newPrefixes, rtrim($route->getPrefix(), '/'));
            }

            $route->addSettings($settings);

            if(!($route instanceof RouterGroup)) {
                if(is_array($newPrefixes) && count($newPrefixes) && $backStack) {
                    $route->setUrl( join('/', $newPrefixes) . $route->getUrl() );
                }

                $this->controllerUrlMap[] = $route;
            }

            $this->currentRoute = $route;

            if($route instanceof RouterGroup && is_callable($route->getCallback())) {
                $route->renderRoute($this->request);
                $activeGroup = $route;
                $mergedSettings = array_merge($route->getMergeableSettings(), $settings);
            }

            $this->currentRoute = null;

            if(count($this->backstack)) {
                $backStack = $this->backstack;
                $this->backstack = array();

                // Route any routes added to the backstack
                $this->processRoutes($backStack, $mergedSettings, $newPrefixes, true, $activeGroup);
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

        $routeNotAllowed = false;

        $max = count($this->controllerUrlMap);

        /* @var $route RouterEntry */
        for($i = 0; $i < $max; $i++) {

            $route = $this->controllerUrlMap[$i];

            $routeMatch = $route->matchRoute($this->request);

            if($routeMatch) {

                if(count($route->getRequestMethods()) && !in_array($this->request->getMethod(), $route->getRequestMethods())) {
                    $routeNotAllowed = true;
                    continue;
                }

                $routeNotAllowed = false;

                $this->loadedRoute = $route;
                $route->loadMiddleware($this->request);
                $route->renderRoute($this->request);
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

    public function arrayToParams(array $getParams = null, $includeEmpty = true) {
        if (is_array($getParams) && count($getParams) > 0) {
            foreach ($getParams as $key => $val) {
                if (!empty($val) || empty($val) && $includeEmpty) {
                    $getParams[$key] = $key . '=' . $val;
                }
            }
            return join('&', $getParams);
        }
        return '';
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
                    $url = str_ireplace(array('{' . $param. '}', '{' . $param. '?}'), $value, $url);
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
            $url .= '?' . $this->arrayToParams($getParams);
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

        if($controller === null && $parameters === null && $this->loadedRoute !== null) {
            return $this->processUrl($this->loadedRoute, null, null, $getParams);
        }

        $c = '';
        $method = null;

        $max = count($this->controllerUrlMap);

        /* @var $route RouterRoute */
        for($i = 0; $i < $max; $i++) {

            $route = $this->controllerUrlMap[$i];

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
        for($i = 0; $i < $max; $i++) {

            $route = $this->controllerUrlMap[$i];

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
            foreach($parameters as $key => $value) {
                array_push($url,$value);
            }
        }

        $url = '/' . trim(join('/', $url), '/') . '/';


        if($getParams !== null && count($getParams)) {
            $url .= '?' . $this->arrayToParams($getParams);
        }

        return $url;
    }

    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

}
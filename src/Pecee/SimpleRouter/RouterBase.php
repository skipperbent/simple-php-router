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
    protected $loadedRoute;
    protected $defaultNamespace;

    // TODO: make interface for controller routers, so they can be easily detected
    // TODO: clean up - cut some of the methods down to smaller pieces

    public function __construct() {
        $this->routes = array();
        $this->backstack = array();
        $this->controllerUrlMap = array();
        $this->requestUri = $_SERVER['REQUEST_URI'];
        $this->requestMethod = (isset($_POST['_method'])) ? strtolower($_POST['_method']) : strtolower($_SERVER['REQUEST_METHOD']);
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

            if($this->defaultNamespace) {
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

            if(!($route instanceof RouterGroup) && is_array($newPrefixes) && count($newPrefixes) && $backstack) {
                $route->setUrl( join('/', $newPrefixes) . $route->getUrl() );
            }

            $this->controllerUrlMap[] = $route;

            $this->currentRoute = $route;
            if($route instanceof RouterGroup && is_callable($route->getCallback())) {
                $route->renderRoute($this->requestMethod);
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
        // Loop through each route-request

        $this->processRoutes($this->routes);

        foreach($this->controllerUrlMap as $route) {
            $routeMatch = $route->matchRoute($this->requestMethod, rtrim($this->requestUri, '/') . '/');

            if($routeMatch && !($routeMatch instanceof RouterGroup)) {
                $this->loadedRoute = $routeMatch;
                $routeMatch->renderRoute($this->requestMethod);
                break;
            }
        }

        if(!$this->loadedRoute) {
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
    public function getLoadedRoute() {
        if(!($this->loadedRoute instanceof RouterGroup)) {
            return $this->loadedRoute;
        }
        return null;
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
        return $this->currentRoute;
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

        if($route instanceof RouterController || $route instanceof RouterRessource) {
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
                    $url = str_ireplace('{' . $param. '}', $value, $route->getUrl());
                    $i++;
                }
            }
        }

        $p = '';
        if($getParams !== null && count($getParams)) {
            $p = '?'.Url::arrayToParams($getParams);
        }

        $url .= $p;

        return $url;
    }

    public function getRoute($controller = null, $parameters = null, $getParams = null) {

        if($parameters !== null && !is_array($parameters)) {
            throw new \InvalidArgumentException('Invalid type for parameter. Must be array or null');
        }

        if($getParams !== null && !is_array($getParams)) {
            throw new \InvalidArgumentException('Invalid type for getParams. Must be array or null');
        }

        $c = '';
        $method = null;

        /* @var $route RouterRoute */
        foreach($this->controllerUrlMap as $route) {

            if($route instanceof RouterRoute && !is_callable($route->getCallback()) && stripos($route->getCallback(), '@') !== false) {
                $c = $route->getCallback();
            } else if($route instanceof RouterController || $route instanceof RouterRessource) {
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

            } else if($route instanceof RouterController || $route instanceof RouterRessource) {
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

        // Nothing found - return current route
        if($this->loadedRoute) {
            $getParams = ($getParams === null) ? array() : $getParams;
            $params = ($this->loadedRoute->getParameters() == null) ? array() : $this->loadedRoute->getParameters();
            $parameters = ($parameters === null) ? array() : $parameters;
            return $this->processUrl($this->loadedRoute, null, array_merge($params, $parameters), array_merge($_GET, $getParams));
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
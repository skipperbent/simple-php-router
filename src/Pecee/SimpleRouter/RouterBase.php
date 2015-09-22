<?php
namespace Pecee\SimpleRouter;

use Pecee\Url;

class RouterBase {

    protected static $instance;

    protected $currentRoute;
    protected $routes;
    protected $controllerUrlMap;
    protected $backstack;
    protected $requestUri;
    protected $requestMethod;
    protected $loadedClass;
    protected $defaultControllerNamespace;

    public function __construct() {
        $this->routes = array();
        $this->backstack = array();
        $this->controllerUrlMap = array();
        $this->requestUri = $_SERVER['REQUEST_URI'];
        $this->requestMethod = strtolower(isset($_GET['_method']) ? $_GET['_method'] : $_SERVER['REQUEST_METHOD']);
    }

    public function addRoute(RouterEntry $route) {
        if($this->currentRoute !== null) {
            $this->backstack[] = $route;
        } else {
            $this->routes[] = $route;
        }
    }

    protected function loadClass($name) {
        if(!class_exists($name)) {
            throw new RouterException(sprintf('Class %s does not exist', $name));
        }

        return new $name();
    }

    public function renderRoute(RouterEntry $route) {
        $this->currentRoute = $route;

        // Load middlewares if any
        if($route->getMiddleware()) {
            $this->loadClass($route->getMiddleware());
        }

        if(is_object($route->getCallback()) && is_callable($route->getCallback())) {

            // When the callback is a function
            call_user_func_array($route->getCallback(), $route->getParameters(), 404);

        } else if(stripos($route->getCallback(), '@') > 0) {
            // When the callback is a method

            $controller = explode('@', $route->getCallback());

            $className = $route->getNamespace() . '\\' . $controller[0];

            $class = $this->loadClass($className);

            $this->loadedClass = $class;

            $method = $controller[1];

            if(!method_exists($class, $method)) {
                throw new RouterException(sprintf('Method %s does not exist in class %s', $method, $className), 404);
            }

            call_user_func_array(array($class, $method), $route->getParameters());
        }
    }

    protected function processRoutes(array $routes, array &$settings = array(), array &$prefixes = array()) {
        // Loop through each route-request
        /* @var $route RouterEntry */
        foreach($routes as $route) {

            if($this->defaultControllerNamespace) {
                $namespace = null;

                if ($route->getNamespace()) {
                    $namespace = $this->defaultControllerNamespace . '\\' . $route->getNamespace();
                } else {
                    $namespace = $this->defaultControllerNamespace;
                }

                $route->setNamespace($namespace);
            }

            $settings = array_merge($settings, $route->getMergeableSettings());
            if($route->getPrefix()) {
                array_push($prefixes, $route->getPrefix());
            }

            $route->setSettings($settings);

            if($route instanceof RouterRoute) {
                if(is_array($prefixes) && count($prefixes)) {
                    $route->setUrl( '/' . join('/', $prefixes) . $route->getUrl() );
                }

                if(stripos($route->getCallback(), '@') !== false) {
                    $this->controllerUrlMap[$route->getCallback()] = $route;
                }
            }

            if($route instanceof RouterController) {

                if(is_array($prefixes) && count($prefixes)) {
                    $route->setUrl( '/' . join('/', $prefixes) . $route->getUrl() );
                }

                $this->controllerUrlMap[$route->getController()] = $route;
            }

            // Stop if the route matches

            $route = $route->getRoute($this->requestMethod, $this->requestUri);
            if($route) {
                $this->renderRoute($route);
            }

            if(count($this->backstack)) {
                // Remove itself from backstack
                array_shift($this->backstack);

                // Route any routes added to the backstack
                $this->processRoutes($this->backstack, $settings, $prefixes);
            }
        }
    }

    public function routeRequest() {
        // Loop through each route-request

        $this->processRoutes($this->routes);
    }

    /**
     * @return string
     */
    public function getDefaultControllerNamespace(){
        return $this->defaultControllerNamespace;
    }

    /**
     * @param string $defaultControllerNamespace
     */
    public function setDefaultControllerNamespace($defaultControllerNamespace) {
        $this->defaultControllerNamespace = $defaultControllerNamespace;
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
        return $this->currentRoute;
    }

    /**
     * @return array
     */
    public function getRoutes(){
        return $this->routes;
    }

    public function getRoute($controller, $parameters = null, $getParams = null) {
        /* @var $route RouterRoute */
        foreach($this->controllerUrlMap as $c => $route) {
            $params = $route->getParameters();

            if(strtolower($c) === strtolower($controller)) {

                $url = $route->getUrl();

                $i = 0;
                foreach($params as $param => $value) {
                    $value = (isset($parameters[$param])) ? $parameters[$param] : $value;
                    $url = str_ireplace('{' . $param. '}', $value, $route->getUrl());
                    $i++;
                }

                $p = '';
                if($getParams !== null) {
                    $p = '?'.Url::arrayToParams($getParams);
                }

                $url .= $p;

                return $url;
            }
        }

        return '/';
    }

    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}
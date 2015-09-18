<?php
namespace Pecee\Router;

class SimpleRouter {

    protected static $instance;

    protected $currentRoute;
    protected $routes;
    protected $backstack;
    protected $requestUri;
    protected $requestMethod;
    protected $loadedClass;

    public function __construct() {
        $this->routes = array();
        $this->backstack = array();
        $this->requestUri = rtrim($_SERVER['REQUEST_URI'], '/');
        $this->requestMethod = strtolower(isset($_GET['_method']) ? $_GET['_method'] : $_SERVER['REQUEST_METHOD']);
    }

    public function addRoute(RouterEntry $route) {
        if($this->currentRoute !== null) {
            $this->backstack[] = $route;
        } else {
            $this->routes[] = $route;
        }
    }

    public function route($url, $callback) {
        $route = new RouterRoute($url, $callback);
        $this->addRoute($route);
        return $route;
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
            call_user_func_array($route->getCallback(), $route->getParameters());

        } else if(stripos($route->getCallback(), '@') > 0) {
            // When the callback is a method

            $controller = explode('@', $route->getCallback());
            $class = $route->getNamespace() . '\\' . $controller[0];

            $class = $this->loadClass($class);

            $this->loadedClass = $class;

            $method = $controller[1];

            if(!method_exists($class, $method)) {
                throw new RouterException(sprintf('Method %s does not exist', $method));
            }

            call_user_func_array(array($class, $method), $route->getParameters());
        }
    }

    protected function renderBackstack(array $routes, &$settings, &$prefixes) {
        // Loop through each route-request
        /* @var $route RouterEntry */
        foreach($routes as $route) {

            $settings = array_merge($settings, $route->getMergeableSettings());
            if($route->getPrefix()) {
                array_push($prefixes, $route->getPrefix());
            }

            // If the route is a group
            if($route instanceof RouterRoute) {
                $route->setSettings($settings);
                $route->setUrl( '/' . join('/', $prefixes) . $route->getUrl() );
            }

            // Stop if the route matches
            $route = $route->getRoute($this->requestMethod, $this->requestUri);
            if($route) {
                $this->renderRoute($route);
            }

            // Remove itself from backstack
            array_shift($this->backstack);

            // Route any routes added to the backstack
            $this->renderBackstack($this->backstack, $settings, $prefixes);
        }
    }

    public function routeRequest() {
        // Loop through each route-request
        /* @var $route RouterEntry */
        foreach($this->routes as $route) {

            // Reset variables
            $settings = array();
            $prefixes = array();

            $settings = array_merge($settings, $route->getMergeableSettings());

            if($route->getPrefix()) {
                array_push($prefixes, $route->getPrefix());
            }

            // Stop if the route matches
            $route = $route->getRoute($this->requestMethod, $this->requestUri);
            if($route) {
                $this->renderRoute($route);
            }

            // Route any routes added to the backstack
            $this->renderBackstack($this->backstack, $settings, $prefixes);
        }
    }

    public static function GetInstance() {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}
<?php
namespace Pecee\SimpleRouter;

use Pecee\Exception\RouterException;
use Pecee\Handler\IExceptionHandler;
use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\Http\Request;

class RouterBase {

    protected static $instance;

    protected $request;
    protected $currentRoute;
    protected $routes;
    protected $processedRoutes;
    protected $controllerUrlMap;
    protected $backStack;
    protected $defaultNamespace;
    protected $bootManagers;
    protected $baseCsrfVerifier;
    protected $exceptionHandlers;

    // TODO: clean up - cut some of the methods down to smaller pieces

    public function __construct() {
        $this->request = Request::getInstance();
        $this->routes = array();
        $this->backStack = array();
        $this->controllerUrlMap = array();
        $this->bootManagers = array();
        $this->exceptionHandlers = array();
    }

    public function addRoute(RouterEntry $route) {
        if($this->currentRoute !== null) {
            $this->backStack[] = $route;
        } else {
            $this->routes[] = $route;
        }
    }

    protected function processRoutes(array $routes, array $settings = array(), array $prefixes = array(), $backStack = false, $group = null) {
        // Loop through each route-request

        $routesCount = count($routes);
        $mergedSettings = array();

        /* @var $route RouterEntry */
        for($i = 0; $i < $routesCount; $i++) {

            $route = $routes[$i];

            $route->addSettings($settings);

            if($backStack) {
                $route->setGroup($group);
            }

            if($this->defaultNamespace && !$route->getNamespace()) {
                $namespace = $this->defaultNamespace;
                if ($route->getNamespace()) {
                    $namespace .= '\\' . $route->getNamespace();
                }

                $route->setNamespace($namespace);
            }

            $newPrefixes = $prefixes;

            if($route->getPrefix() && trim($route->getPrefix(), '/') !== '') {
                array_push($newPrefixes, trim($route->getPrefix(), '/'));
            }

            /* @var $group RouterGroup */
            $group = null;

            if(!($route instanceof RouterGroup)) {
                if(is_array($newPrefixes) && count($newPrefixes) && $backStack) {
                    $route->setUrl( '/' . join('/', $newPrefixes) . $route->getUrl() );
                }

                $this->controllerUrlMap[] = $route;
            }

            $this->currentRoute = $route;

            if($route instanceof RouterGroup && is_callable($route->getCallback())) {
                $group = $route;

                $group->renderRoute($this->request);
                $mergedSettings = array_merge($settings, $group->getMergeableSettings());

                // Add ExceptionHandler
                if($group->matchRoute($this->request) && $group->getExceptionHandler() !== null) {
                    $this->exceptionHandlers[] = $route;
                }
            }

            $this->currentRoute = null;

            if(count($this->backStack)) {
                $backStack = $this->backStack;
                $this->backStack = array();

                // Route any routes added to the backstack
                $this->processRoutes($backStack, $mergedSettings, $newPrefixes, true, $group);
            }
        }
    }

    public function routeRequest($original = true) {

        $originalUri = $this->request->getUri();

        $routeNotAllowed = false;

        try {

            // Initialize boot-managers
            if(count($this->bootManagers)) {
                /* @var $manager RouterBootManager */
                foreach($this->bootManagers as $manager) {
                    $this->request = $manager->boot($this->request);

                    if(!($this->request instanceof Request)) {
                        throw new RouterException('Custom router bootmanager "'. get_class($manager) .'" must return instance of Request.');
                    }
                }
            }

            // Loop through each route-request
            $this->processRoutes($this->routes);

            if($original === true) {
                // Verify csrf token for request
                if ($this->baseCsrfVerifier !== null) {
                    $this->baseCsrfVerifier->handle($this->request);
                }
            }

            $max = count($this->controllerUrlMap);

            /* @var $route RouterEntry */
            for ($i = 0; $i < $max; $i++) {

                $route = $this->controllerUrlMap[$i];

                $routeMatch = $route->matchRoute($this->request);

                if ($routeMatch) {

                    if (count($route->getRequestMethods()) && !in_array($this->request->getMethod(), $route->getRequestMethods())) {
                        $routeNotAllowed = true;
                        continue;
                    }

                    $routeNotAllowed = false;

                    $this->request->rewrite_uri = $this->request->uri;
                    $this->request->setUri($originalUri);

                    $this->request->loadedRoute = $route;
                    $route->loadMiddleware($this->request);

                    $this->request->loadedRoute->renderRoute($this->request);

                    break;
                }
            }

        } catch(\Exception $e) {
            $this->handleException($e);
        }

        if($routeNotAllowed) {
            $this->handleException(new RouterException('Route or method not allowed', 403));
        }

        if(!$this->request->loadedRoute) {
            $this->handleException(new RouterException(sprintf('Route not found: %s', $this->request->getUri()), 404));
        }
    }

    protected function handleException(\Exception $e) {

        $request = null;

        /* @var $route RouterGroup */
        foreach ($this->exceptionHandlers as $route) {
            $route->loadMiddleware($this->request);
            $handler = $route->getExceptionHandler();
            $handler = new $handler();

            if (!($handler instanceof IExceptionHandler)) {
                throw new RouterException('Exception handler must implement the IExceptionHandler interface.');
            }

            $request = $handler->handleError($this->request, $this->request->loadedRoute, $e);
        }

        if($request !== null) {
            $this->request = $request;
            $this->routeRequest(false);
            return;
        }

        throw $e;
    }

    /**
     * @return string
     */
    public function getDefaultNamespace(){
        return $this->defaultNamespace;
    }

    /**
     * @param string $defaultNamespace
     * @return static
     */
    public function setDefaultNamespace($defaultNamespace) {
        $this->defaultNamespace = $defaultNamespace;
        return $this;
    }

    /**
     * @return array
     */
    public function getBootManagers() {
        return $this->bootManagers;
    }

    /**
     * @param array $bootManagers
     */
    public function setBootManagers(array $bootManagers) {
        $this->bootManagers = $bootManagers;
    }

    public function addBootManager(RouterBootManager $bootManager) {
        $this->bootManagers[] = $bootManager;
    }

    /**
     * @return RouterEntry
     */
    public function getLoadedRoute() {
        if(!($this->request->loadedRoute instanceof RouterGroup)) {
            return $this->request->loadedRoute;
        }
        return null;
    }

    /**
     * @return array
     */
    public function getBackstack() {
        return $this->backStack;
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

        if(is_array($getParams) && count($getParams)) {
            if ($includeEmpty === false) {
                $getParams = array_filter($getParams, function ($item) {
                    return (!empty($item));
                });
            }

            return '?' . http_build_query($getParams);
        }

        return '';
    }

    protected function processUrl(RouterRoute $route, $method = null, $parameters = null, $getParams = null) {

        $domain = '';

        if($route->getGroup() !== null && $route->getGroup()->getDomain() !== null) {
            if(is_array($route->getGroup()->getDomain())) {
                $domains = $route->getGroup()->getDomain();
                $domain = array_shift($domains);
            } else {
                $domain = $route->getGroup()->getDomain();
            }

            $domain = '//' . $domain;
        }

        $url = $domain . '/' . trim($route->getUrl(), '/');

        if(($route instanceof RouterController || $route instanceof RouterResource) && $method !== null) {
            $url .= $method;
        }

        if($route instanceof RouterController || $route instanceof RouterResource) {
            if(count($parameters)) {
                $url .= join('/', $parameters);
            }
        } else {
            /* @var $route RouterEntry */
            if(is_array($parameters)) {
                $params = array_merge($route->getParameters(), $parameters);
            } else {
                $params = $route->getParameters();
            }

            $otherParams = [];

            $i = 0;
            foreach($params as $param => $value) {
                $value = (isset($parameters[$param])) ? $parameters[$param] : $value;
                if(stripos($url, '{' . $param. '}') !== false || stripos($url, '{' . $param . '?}') !== false) {
                    $url = str_ireplace(array('{' . $param . '}', '{' . $param . '?}'), $value, $url);
                } else {
                    $otherParams[$param] = $value;
                }
                $i++;
            }

            $url = rtrim($url, '/') . '/' . join('/', $otherParams);
        }

        $url = rtrim($url, '/') . '/';

        if($getParams !== null) {
            $url .= $this->arrayToParams($getParams);
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

        // Return current route if no options has been specified
        if($controller === null && $parameters === null) {
            $getParams = (is_array($getParams)) ? array_merge($_GET, $getParams) : $_GET;

            $url = parse_url($this->request->getUri(), PHP_URL_PATH);

            if($getParams !== null) {
                $url .= $this->arrayToParams($getParams);
            }

            return $url;
        }

        if($controller === null && $this->request->loadedRoute !== null) {
            return $this->processUrl($this->request->loadedRoute, $this->request->loadedRoute->getMethod(), $parameters, $getParams);
        }

        $c = '';
        $method = null;

        $max = count($this->controllerUrlMap);

        /* @var $route RouterRoute */
        for($i = 0; $i < $max; $i++) {

            $route = $this->controllerUrlMap[$i];

            // Check an alias exist, if the matches - use it
            if($route instanceof RouterRoute && $route->hasAlias($controller)) {
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

        if($getParams !== null) {
            $url .= $this->arrayToParams($getParams);
        }

        return $url;
    }

    public static function getInstance() {
        if(static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public static function reset() {
        static::$instance = null;
    }

}
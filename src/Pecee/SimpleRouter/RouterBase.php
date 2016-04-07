<?php
namespace Pecee\SimpleRouter;

use Pecee\CsrfToken;
use Pecee\Exception\RouterException;
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

    // TODO: clean up - cut some of the methods down to smaller pieces

    public function __construct() {
        $this->routes = array();
        $this->backStack = array();
        $this->controllerUrlMap = array();
        $this->baseCsrfVerifier = new BaseCsrfVerifier();
        $this->request = Request::getInstance();
        $this->bootManagers = array();

        $csrf = new CsrfToken();
        $token = ($csrf->hasToken()) ? $csrf->getToken() : $csrf->generateToken();
        $csrf->setToken($token);
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

            if(!($route instanceof RouterGroup)) {
                if(is_array($newPrefixes) && count($newPrefixes) && $backStack) {
                    $route->setUrl( join('/', $newPrefixes) . $route->getUrl() );
                }

                $group = null;
                $this->controllerUrlMap[] = $route;
            }

            $this->currentRoute = $route;

            if($route instanceof RouterGroup && is_callable($route->getCallback())) {
                $group = $route;
                $route->renderRoute($this->request);
                $mergedSettings = array_merge($settings, $route->getMergeableSettings());
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

    public function routeRequest() {

        $originalUri = $this->request->getUri();

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

        // Make sure routes with longer urls are rendered first
        usort($this->controllerUrlMap, function($a, $b) {
            if(strlen($a->getUrl()) < strlen($b->getUrl())) {
                return 1;
            }
            return -1;
        });

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

                $this->request->rewrite_uri = $this->request->uri;
                $this->request->setUri($originalUri);

                $this->request->loadedRoute = $route;
                $route->loadMiddleware($this->request);

                $this->request->loadedRoute->renderRoute($this->request);
                break;
            }
        }

        if($routeNotAllowed) {
            throw new RouterException('Route or method not allowed', 403);
        }

        if(!$this->request->loadedRoute) {
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

        // Return current route if no options has been specified
        if($controller === null && $parameters === null) {
            $getParams = (is_array($getParams)) ? array_merge($_GET, $getParams) : $_GET;

            $url = parse_url(Request::getInstance()->getUri());
            $url = $url['path'];

            if(count($getParams)) {
                $url .= '?' . $this->arrayToParams($getParams);
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
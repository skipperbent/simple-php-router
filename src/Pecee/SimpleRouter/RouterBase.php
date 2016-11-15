<?php
namespace Pecee\SimpleRouter;

use Pecee\Exception\RouterException;
use Pecee\Handler\IExceptionHandler;
use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\Http\Request;
use Pecee\Http\Response;

class RouterBase {

    protected static $instance;

    /**
     * Current request
     * @var Request
     */
    protected $request;

    /**
     * Response
     * @var Response
     */
    protected $response;

    /**
     * Used to keep track of whether to add routes to stack or not.
     * @var RouterEntry
     */
    protected $currentRoute;

    /**
     * All added routes
     * @var array
     */
    protected $routes;

    /**
     * List of
     * @var array
     */
    protected $controllerUrlMap;

    /**
     * Backstack array used to keep track of sub-routes
     * @var array
     */
    protected $backStack;

    /**
     * The default namespace that all routes will inherit
     * @var string
     */
    protected $defaultNamespace;

    /**
     * List of added bootmanagers
     * @var array
     */
    protected $bootManagers;

    /**
     * Csrf verifier class
     * @var BaseCsrfVerifier
     */
    protected $csrfVerifier;

    /**
     * Get exception handlers
     * @var array
     */
    protected $exceptionHandlers;

    public function __construct() {
        $this->reset();
    }

    public function reset() {
        $this->request = new Request();
        $this->response = new Response($this->request);
        $this->routes = array();
        $this->backStack = array();
        $this->controllerUrlMap = array();
        $this->bootManagers = array();
        $this->exceptionHandlers = array();
    }

    /**
     * Add route
     * @param RouterEntry $route
     * @return RouterEntry
     */
    public function addRoute(RouterEntry $route) {
        if($this->currentRoute !== null) {
            $this->backStack[] = $route;
        } else {
            $this->routes[] = $route;
        }

        return $route;
    }

    protected function processRoutes(array $routes, array $settings = array(), array $prefixes = array(), $backStack = false, RouterGroup $group = null) {
        // Loop through each route-request

        $mergedSettings = array();

        /* @var $route RouterEntry */
        for($i = 0; $i < count($routes); $i++) {

            $route = $routes[$i];

            if(count($settings)) {
                $route->addSettings($settings);
            }

            if($backStack && $group !== null) {
                $route->setGroup($group);
            }

            if($route->getNamespace() === null && $this->defaultNamespace !== null) {
                $namespace = $this->defaultNamespace;
                if ($route->getNamespace()) {
                    $namespace .= '\\' . $route->getNamespace();
                }

                $route->setNamespace($namespace);
            }

            if($group !== null && $group->getPrefix() !== null && trim($group->getPrefix(), '/') !== '') {
                $prefixes[] = trim($group->getPrefix(), '/');
            }

            $group = null;
            $this->currentRoute = $route;

            if($route instanceof ILoadableRoute) {
                if(is_array($prefixes) && count($prefixes) && $backStack) {
                    $route->setUrl( '/' . join('/', $prefixes) . $route->getUrl() );
                }

                $this->controllerUrlMap[] = $route;
            } else {
                if(is_callable($route->getCallback())) {

                    $route->renderRoute($this->request);

                    if ($route->matchRoute($this->request)) {

                        /* @var $group RouterGroup */
                        $group = $route;

                        $mergedSettings = array_merge($settings, $group->getMergeableSettings());

                        // Add ExceptionHandler
                        if ($group->getExceptionHandler() !== null) {
                            $this->exceptionHandlers[] = $route;
                        }

                    }
                }
            }

            $this->currentRoute = null;

            if(count($this->backStack)) {
                $backStack = $this->backStack;
                $this->backStack = array();

                // Route any routes added to the backstack
                $this->processRoutes($backStack, $mergedSettings, $prefixes, true, $group);
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

            if($original === true && $this->csrfVerifier !== null) {
                // Verify csrf token for request
                $this->csrfVerifier->handle($this->request);
            }

            /* @var $route RouterEntry */
            for ($i = 0; $i < count($this->controllerUrlMap); $i++) {

                $route = $this->controllerUrlMap[$i];

                if ($route->matchRoute($this->request)) {

                    if (count($route->getRequestMethods()) && !in_array($this->request->getMethod(), $route->getRequestMethods())) {
                        $routeNotAllowed = true;
                        continue;
                    }

                    $routeNotAllowed = false;

                    $this->request->rewrite_uri = $this->request->getUri();
                    $this->request->setUri($originalUri);

                    $this->request->loadedRoute = $route;
                    $this->request->loadedRoute->loadMiddleware($this->request);

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
     * Get default namespace
     * @return string
     */
    public function getDefaultNamespace(){
        return $this->defaultNamespace;
    }

    /**
     * Set the main default namespace that all routes will inherit
     * @param string $defaultNamespace
     * @return static
     */
    public function setDefaultNamespace($defaultNamespace) {
        $this->defaultNamespace = $defaultNamespace;
        return $this;
    }

    /**
     * Get bootmanagers
     * @return array
     */
    public function getBootManagers() {
        return $this->bootManagers;
    }

    /**
     * Set bootmanagers
     * @param array $bootManagers
     */
    public function setBootManagers(array $bootManagers) {
        $this->bootManagers = $bootManagers;
    }

    /**
     * Add bootmanager
     * @param RouterBootManager $bootManager
     */
    public function addBootManager(RouterBootManager $bootManager) {
        $this->bootManagers[] = $bootManager;
    }

    /**
     * Get the loaded route
     * @return RouterEntry
     */
    public function getLoadedRoute() {
        return $this->request->loadedRoute;
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
     * Get response
     * @return Response
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Get csrf verifier class
     * @return BaseCsrfVerifier
     */
    public function getCsrfVerifier() {
        return $this->csrfVerifier;
    }

    /**
     * Set csrf verifier class
     *
     * @param BaseCsrfVerifier $csrfVerifier
     * @return static
     */
    public function setCsrfVerifier(BaseCsrfVerifier $csrfVerifier) {
        $this->csrfVerifier = $csrfVerifier;
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

        if($route instanceof IControllerRoute && $method !== null) {
            $url .= $method;

            if(count($parameters)) {
                $url .= join('/', $parameters);
            }
        } else {
            if($parameters !== null && is_array($parameters)) {
                $params = array_merge($route->getParameters(), $parameters);
            } else {
                $params = $route->getParameters();
            }

            $otherParams = array();
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
            $getParams = ($getParams !== null && is_array($getParams)) ? array_merge($_GET, $getParams) : $_GET;

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
            if($route instanceof IControllerRoute) {
                $c = $route->getController();
            } else {
                if($route->hasAlias($controller)) {
                    return $this->processUrl($route, $route->getMethod(), $parameters, $getParams);
                }

                if(!is_callable($route->getCallback()) && stripos($route->getCallback(), '@') !== false) {
                    $c = $route->getCallback();
                }
            }

            if($c === $controller || strpos($c, $controller) === 0) {
                return $this->processUrl($route, $route->getMethod(), $parameters, $getParams);
            }
        }

        $c = '';

        // No match has yet been found, let's try to guess what url that should be returned
        for($i = 0; $i < $max; $i++) {

            $route = $this->controllerUrlMap[$i];

            if($route instanceof IControllerRoute) {
                $c = $route->getController();
            } else if(!is_callable($route->getCallback()) && stripos($route->getCallback(), '@') !== false) {
                $c = $route->getClass();
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

        if($parameters !== null && is_array($parameters) && count($parameters)) {
            $url = array_merge($url, $parameters);
        }

        $url = '/' . trim(join('/', $url), '/') . '/';

        if($getParams !== null) {
            $url .= $this->arrayToParams($getParams);
        }

        return $url;
    }

    /**
     * Get current router instance
     * @return static
     */
    public static function getInstance() {
        if(static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

}
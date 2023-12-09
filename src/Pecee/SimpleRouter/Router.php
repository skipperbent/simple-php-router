<?php

namespace Pecee\SimpleRouter;

use Exception;
use Pecee\Exceptions\InvalidArgumentException;
use Pecee\Http\Exceptions\MalformedUrlException;
use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\Http\Request;
use Pecee\Http\Url;
use Pecee\SimpleRouter\ClassLoader\ClassLoader;
use Pecee\SimpleRouter\ClassLoader\IClassLoader;
use Pecee\SimpleRouter\Exceptions\HttpException;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\Handlers\EventHandler;
use Pecee\SimpleRouter\Handlers\IEventHandler;
use Pecee\SimpleRouter\Handlers\IExceptionHandler;
use Pecee\SimpleRouter\Route\IControllerRoute;
use Pecee\SimpleRouter\Route\IGroupRoute;
use Pecee\SimpleRouter\Route\ILoadableRoute;
use Pecee\SimpleRouter\Route\IPartialGroupRoute;
use Pecee\SimpleRouter\Route\IRoute;

class Router
{

    /**
     * Current request
     * @var Request
     */
    protected Request $request;

    /**
     * Defines if a route is currently being processed.
     * @var bool
     */
    protected bool $isProcessingRoute;

    /**
     * Defines all data from current processing route.
     * @var ILoadableRoute
     */
    protected ILoadableRoute $currentProcessingRoute;

    /**
     * All added routes
     * @var array
     */
    protected array $routes = [];

    /**
     * List of processed routes
     * @var array|ILoadableRoute[]
     */
    protected array $processedRoutes = [];

    /**
     * Stack of routes used to keep track of sub-routes added
     * when a route is being processed.
     * @var array
     */
    protected array $routeStack = [];

    /**
     * List of added bootmanagers
     * @var array
     */
    protected array $bootManagers = [];

    /**
     * Csrf verifier class
     * @var BaseCsrfVerifier|null
     */
    protected ?BaseCsrfVerifier $csrfVerifier;

    /**
     * Get exception handlers
     * @var array
     */
    protected array $exceptionHandlers = [];

    /**
     * List of loaded exception that has been loaded.
     * Used to ensure that exception-handlers aren't loaded twice when rewriting route.
     *
     * @var array
     */
    protected array $loadedExceptionHandlers = [];

    /**
     * Enable or disabled debugging
     * @var bool
     */
    protected bool $debugEnabled = false;

    /**
     * The start time used when debugging is enabled
     * @var float
     */
    protected float $debugStartTime;

    /**
     * List containing all debug messages
     * @var array
     */
    protected array $debugList = [];

    /**
     * Contains any registered event-handler.
     * @var array
     */
    protected array $eventHandlers = [];

    /**
     * Class loader instance
     * @var IClassLoader
     */
    protected IClassLoader $classLoader;

    /**
     * When enabled the router will render all routes that matches.
     * When disabled the router will stop execution when first route is found.
     * @var bool
     */
    protected bool $renderMultipleRoutes = false;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Resets the router by reloading request and clearing all routes and data.
     */
    public function reset(): void
    {
        $this->debugStartTime = microtime(true);
        $this->isProcessingRoute = false;

        try {
            $this->request = new Request();
        } catch (MalformedUrlException $e) {
            $this->debug(sprintf('Invalid request-uri url: %s', $e->getMessage()));
        }

        $this->routes = [];
        $this->bootManagers = [];
        $this->routeStack = [];
        $this->processedRoutes = [];
        $this->exceptionHandlers = [];
        $this->loadedExceptionHandlers = [];
        $this->eventHandlers = [];
        $this->debugList = [];
        $this->csrfVerifier = null;
        $this->classLoader = new ClassLoader();
    }

    /**
     * Add route
     * @param IRoute $route
     * @return IRoute
     */
    public function addRoute(IRoute $route): IRoute
    {
        $this->fireEvents(EventHandler::EVENT_ADD_ROUTE, [
            'route' => $route,
            'isSubRoute' => $this->isProcessingRoute,
        ]);

        /*
         * If a route is currently being processed, that means that the route being added are rendered from the parent
         * routes callback, so we add them to the stack instead.
         */
        if ($this->isProcessingRoute === true) {
            $this->routeStack[] = $route;
        } else {
            $this->routes[] = $route;
        }

        return $route;
    }

    /**
     * Render and process any new routes added.
     *
     * @param IRoute $route
     * @throws NotFoundHttpException
     */
    protected function renderAndProcess(IRoute $route): void
    {
        $this->isProcessingRoute = true;
        $route->renderRoute($this->request, $this);
        $this->isProcessingRoute = false;

        if (count($this->routeStack) !== 0) {

            /* Pop and grab the routes added when executing group callback earlier */
            $stack = $this->routeStack;
            $this->routeStack = [];

            /* Route any routes added to the stack */
            $this->processRoutes($stack, ($route instanceof IGroupRoute) ? $route : null);
        }
    }

    /**
     * Process added routes.
     *
     * @param array|IRoute[] $routes
     * @param IGroupRoute|null $group
     * @throws NotFoundHttpException
     */
    protected function processRoutes(array $routes, ?IGroupRoute $group = null): void
    {
        $this->debug('Processing routes');

        // Stop processing routes if no valid route is found.
        if ($this->request->getRewriteRoute() === null && $this->request->getUrl()->getOriginalUrl() === '') {
            $this->debug('Halted route-processing as no valid route was found');

            return;
        }

        $url = $this->request->getRewriteUrl() ?? $this->request->getUrl()->getPath();

        // Loop through each route-request
        foreach ($routes as $route) {

            $this->debug('Processing route "%s"', get_class($route));

            if ($group !== null) {
                /* Add the parent group */
                $route->setGroup($group);
            }

            /* @var $route IGroupRoute */
            if ($route instanceof IGroupRoute) {

                if ($route->matchRoute($url, $this->request) === true) {

                    /* Add exception handlers */
                    if (count($route->getExceptionHandlers()) !== 0) {

                        if ($route->getMergeExceptionHandlers() === true) {

                            foreach ($route->getExceptionHandlers() as $handler) {
                                $this->exceptionHandlers[] = $handler;
                            }

                        } else {
                            $this->exceptionHandlers = $route->getExceptionHandlers();
                        }
                    }

                    /* Only render partial group if it matches */
                    if ($route instanceof IPartialGroupRoute === true) {
                        $this->renderAndProcess($route);
                        continue;
                    }

                }

                if ($route instanceof IPartialGroupRoute === false) {
                    $this->renderAndProcess($route);
                }

                continue;
            }

            if ($route instanceof ILoadableRoute === true) {

                /* Add the route to the map, so we can find the active one when all routes has been loaded */
                $this->processedRoutes[] = $route;
            }
        }
    }

    /**
     * Load routes
     * @return void
     * @throws NotFoundHttpException
     */
    public function loadRoutes(): void
    {
        $this->debug('Loading routes');

        $this->fireEvents(EventHandler::EVENT_LOAD_ROUTES, [
            'routes' => $this->routes,
        ]);

        /* Loop through each route-request */
        $this->processRoutes($this->routes);

        $this->fireEvents(EventHandler::EVENT_BOOT, [
            'bootmanagers' => $this->bootManagers,
        ]);

        /* Initialize boot-managers */

        /* @var $manager IRouterBootManager */
        foreach ($this->bootManagers as $manager) {

            $className = get_class($manager);
            $this->debug('Rendering bootmanager "%s"', $className);
            $this->fireEvents(EventHandler::EVENT_RENDER_BOOTMANAGER, [
                'bootmanagers' => $this->bootManagers,
                'bootmanager' => $manager,
            ]);

            /* Render bootmanager */
            $manager->boot($this, $this->request);

            $this->debug('Finished rendering bootmanager "%s"', $className);
        }

        $this->debug('Finished loading routes');
    }

    /**
     * Start the routing
     *
     * @return string|null
     * @throws NotFoundHttpException
     * @throws \Pecee\Http\Middleware\Exceptions\TokenMismatchException
     * @throws HttpException
     * @throws Exception
     */
    public function start(): ?string
    {
        $this->debug('Router starting');

        $this->fireEvents(EventHandler::EVENT_INIT);

        $this->loadRoutes();

        if ($this->csrfVerifier !== null) {

            $this->fireEvents(EventHandler::EVENT_RENDER_CSRF, [
                'csrfVerifier' => $this->csrfVerifier,
            ]);

            try {
                /* Verify csrf token for request */
                $this->csrfVerifier->handle($this->request);
            } catch (Exception $e) {
                return $this->handleException($e);
            }
        }

        $output = $this->routeRequest();

        $this->fireEvents(EventHandler::EVENT_LOAD, [
            'loadedRoutes' => $this->getRequest()->getLoadedRoutes(),
        ]);

        $this->debug('Routing complete');

        return $output;
    }

    /**
     * Routes the request
     *
     * @return string|null
     * @throws HttpException
     * @throws Exception
     */
    public function routeRequest(): ?string
    {
        $this->debug('Routing request');

        $methodNotAllowed = null;

        try {
            $url = $this->request->getRewriteUrl() ?? $this->request->getUrl()->getPath();

            /* @var $route ILoadableRoute */
            foreach ($this->processedRoutes as $key => $route) {

                $this->debug('Matching route "%s"', get_class($route));

                /* Add current processing route to constants */
                $this->currentProcessingRoute = $route;

                /* If the route matches */
                if ($route->matchRoute($url, $this->request) === true) {

                    $this->fireEvents(EventHandler::EVENT_MATCH_ROUTE, [
                        'route' => $route,
                    ]);

                    /* Check if request method matches */
                    if (count($route->getRequestMethods()) !== 0 && in_array($this->request->getMethod(), $route->getRequestMethods(), true) === false) {
                        $this->debug('Method "%s" not allowed', $this->request->getMethod());

                        // Only set method not allowed is not already set
                        if ($methodNotAllowed === null) {
                            $methodNotAllowed = true;
                        }

                        continue;
                    }

                    $this->fireEvents(EventHandler::EVENT_RENDER_MIDDLEWARES, [
                        'route' => $route,
                        'middlewares' => $route->getMiddlewares(),
                    ]);

                    $route->loadMiddleware($this->request, $this);

                    $output = $this->handleRouteRewrite($key, $url);
                    if ($output !== null) {
                        return $output;
                    }

                    $methodNotAllowed = false;

                    $this->request->addLoadedRoute($route);

                    $this->fireEvents(EventHandler::EVENT_RENDER_ROUTE, [
                        'route' => $route,
                    ]);

                    $routeOutput = $route->renderRoute($this->request, $this);

                    if ($this->renderMultipleRoutes === true) {
                        if ($routeOutput !== '') {
                            return $routeOutput;
                        }

                        $output = $this->handleRouteRewrite($key, $url);
                        if ($output !== null) {
                            return $output;
                        }
                    } else {
                        $output = $this->handleRouteRewrite($key, $url);

                        return $output ?? $routeOutput;
                    }
                }
            }

        } catch (Exception $e) {
            return $this->handleException($e);
        }

        if ($methodNotAllowed === true) {
            $message = sprintf('Route "%s" or method "%s" not allowed.', $this->request->getUrl()->getPath(), $this->request->getMethod());
            return $this->handleException(new NotFoundHttpException($message, 403));
        }

        if (count($this->request->getLoadedRoutes()) === 0) {

            $rewriteUrl = $this->request->getRewriteUrl();

            if ($rewriteUrl !== null) {
                $message = sprintf('Route not found: "%s" (rewrite from: "%s")', $rewriteUrl, $this->request->getUrl()->getPath());
            } else {
                $message = sprintf('Route not found: "%s"', $this->request->getUrl()->getPath());
            }

            $this->debug($message);

            return $this->handleException(new NotFoundHttpException($message, 404));
        }

        return null;
    }

    /**
     * Handle route-rewrite
     *
     * @param string $key
     * @param string $url
     * @return string|null
     * @throws HttpException
     * @throws Exception
     */
    protected function handleRouteRewrite(string $key, string $url): ?string
    {
        /* If the request has changed */
        if ($this->request->hasPendingRewrite() === false) {
            return null;
        }

        $route = $this->request->getRewriteRoute();

        if ($route !== null) {
            /* Add rewrite route */
            $this->processedRoutes[] = $route;
        }

        if ($this->request->getRewriteUrl() !== $url) {

            unset($this->processedRoutes[$key]);

            $this->request->setHasPendingRewrite(false);

            $this->fireEvents(EventHandler::EVENT_REWRITE, [
                'rewriteUrl' => $this->request->getRewriteUrl(),
                'rewriteRoute' => $this->request->getRewriteRoute(),
            ]);

            return $this->routeRequest();
        }

        return null;
    }

    /**
     * @param Exception $e
     * @return string|null
     * @throws Exception
     * @throws HttpException
     */
    protected function handleException(Exception $e): ?string
    {
        $this->debug('Starting exception handling for "%s"', get_class($e));

        $this->fireEvents(EventHandler::EVENT_LOAD_EXCEPTIONS, [
            'exception' => $e,
            'exceptionHandlers' => $this->exceptionHandlers,
        ]);

        /* @var $handler IExceptionHandler */
        foreach (array_reverse($this->exceptionHandlers) as $key => $handler) {

            if (is_object($handler) === false) {
                $handler = new $handler();
            }

            $this->fireEvents(EventHandler::EVENT_RENDER_EXCEPTION, [
                'exception' => $e,
                'exceptionHandler' => $handler,
                'exceptionHandlers' => $this->exceptionHandlers,
            ]);

            $this->debug('Processing exception-handler "%s"', get_class($handler));

            if (($handler instanceof IExceptionHandler) === false) {
                throw new HttpException('Exception handler must implement the IExceptionHandler interface.', 500);
            }

            try {
                $this->debug('Start rendering exception handler');
                $handler->handleError($this->request, $e);
                $this->debug('Finished rendering exception-handler');

                if (isset($this->loadedExceptionHandlers[$key]) === false && $this->request->hasPendingRewrite() === true) {

                    $this->loadedExceptionHandlers[$key] = $handler;

                    $this->debug('Exception handler contains rewrite, reloading routes');

                    $this->fireEvents(EventHandler::EVENT_REWRITE, [
                        'rewriteUrl' => $this->request->getRewriteUrl(),
                        'rewriteRoute' => $this->request->getRewriteRoute(),
                    ]);

                    if ($this->request->getRewriteRoute() !== null) {
                        $this->processedRoutes[] = $this->request->getRewriteRoute();
                    }

                    return $this->routeRequest();
                }

            } catch (Exception $e) {

            }

            $this->debug('Finished processing');
        }

        $this->debug('Finished exception handling - exception not handled, throwing');
        throw $e;
    }

    /**
     * Find route by alias, class, callback or method.
     *
     * @param string $name
     * @return ILoadableRoute|null
     */
    public function findRoute(string $name): ?ILoadableRoute
    {
        $this->debug('Finding route by name "%s"', $name);

        $this->fireEvents(EventHandler::EVENT_FIND_ROUTE, [
            'name' => $name,
        ]);

        foreach ($this->processedRoutes as $route) {

            /* Check if the name matches with a name on the route. Should match either router alias or controller alias. */
            if ($route->hasName($name) === true) {
                $this->debug('Found route "%s" by name "%s"', $route->getUrl(), $name);

                return $route;
            }

            /* Direct match to controller */
            if ($route instanceof IControllerRoute && strtoupper($route->getController()) === strtoupper($name)) {
                $this->debug('Found route "%s" by controller "%s"', $route->getUrl(), $name);

                return $route;
            }

            /* Using @ is most definitely a controller@method or alias@method */
            if (strpos($name, '@') !== false) {
                [$controller, $method] = array_map('strtolower', explode('@', $name));

                if ($controller === strtolower((string)$route->getClass()) && $method === strtolower((string)$route->getMethod())) {
                    $this->debug('Found route "%s" by controller "%s" and method "%s"', $route->getUrl(), $controller, $method);

                    return $route;
                }
            }

            /* Check if callback matches (if it's not a function) */
            $callback = $route->getCallback();
            if (is_string($callback) === true && is_callable($callback) === false && strpos($name, '@') !== false && strpos($callback, '@') !== false) {

                /* Check if the entire callback is matching */
                if (strpos($callback, $name) === 0 || strtolower($callback) === strtolower($name)) {
                    $this->debug('Found route "%s" by callback "%s"', $route->getUrl(), $name);

                    return $route;
                }

                /* Check if the class part of the callback matches (class@method) */
                if (strtolower($name) === strtolower($route->getClass())) {
                    $this->debug('Found route "%s" by class "%s"', $route->getUrl(), $name);

                    return $route;
                }
            }
        }

        $this->debug('Route not found');

        return null;
    }

    /**
     * Get url for a route by using either name/alias, class or method name.
     *
     * The name parameter supports the following values:
     * - Route name
     * - Controller/resource name (with or without method)
     * - Controller class name
     *
     * When searching for controller/resource by name, you can use this syntax "route.name@method".
     * You can also use the same syntax when searching for a specific controller-class "MyController@home".
     * If no arguments is specified, it will return the url for the current loaded route.
     *
     * @param string|null $name
     * @param string|array|null $parameters
     * @param array|null $getParams
     * @return Url
     * @throws InvalidArgumentException
     */
    public function getUrl(?string $name = null, $parameters = null, ?array $getParams = null): Url
    {
        $this->debug('Finding url', func_get_args());

        $this->fireEvents(EventHandler::EVENT_GET_URL, [
            'name' => $name,
            'parameters' => $parameters,
            'getParams' => $getParams,
        ]);

        if ($name === '' && $parameters === '') {
            return new Url('/');
        }

        /* Only merge $_GET when all parameters are null */
        $getParams = ($name === null && $parameters === null && $getParams === null) ? $_GET : (array)$getParams;

        /* Return current route if no options has been specified */
        if ($name === null && $parameters === null) {
            return $this->request
                ->getUrlCopy()
                ->setParams($getParams);
        }

        $loadedRoute = $this->request->getLoadedRoute();

        /* If nothing is defined and a route is loaded we use that */
        if ($name === null && $loadedRoute !== null) {
            return $this->request->getUrlCopy()->parse($loadedRoute->findUrl($loadedRoute->getMethod(), $parameters, $name))->setParams($getParams);
        }

        if ($name !== null) {
            /* We try to find a match on the given name */
            $route = $this->findRoute($name);

            if ($route !== null) {
                return $this->request->getUrlCopy()->parse($route->findUrl($route->getMethod(), $parameters, $name))->setParams($getParams);
            }
        }

        /* Using @ is most definitely a controller@method or alias@method */
        if (is_string($name) === true && strpos($name, '@') !== false) {
            [$controller, $method] = explode('@', $name);

            /* Loop through all the routes to see if we can find a match */

            /* @var $route ILoadableRoute */
            foreach ($this->processedRoutes as $processedRoute) {

                /* Check if the route contains the name/alias */
                if ($processedRoute->hasName($controller) === true) {
                    return $this->request->getUrlCopy()->parse($processedRoute->findUrl($method, $parameters, $name))->setParams($getParams);
                }

                /* Check if the route controller is equal to the name */
                if ($processedRoute instanceof IControllerRoute && strtolower($processedRoute->getController()) === strtolower($controller)) {
                    return $this->request->getUrlCopy()->parse($processedRoute->findUrl($method, $parameters, $name))->setParams($getParams);
                }

            }
        }

        /* No result so we assume that someone is using a hardcoded url and join everything together. */
        $url = trim(implode('/', array_merge((array)$name, (array)$parameters)), '/');
        $url = (($url === '') ? '/' : '/' . $url . '/');

        return $this->request->getUrlCopy()->parse($url)->setParams($getParams);
    }

    /**
     * Get BootManagers
     * @return array
     */
    public function getBootManagers(): array
    {
        return $this->bootManagers;
    }

    /**
     * Set BootManagers
     *
     * @param array $bootManagers
     * @return static
     */
    public function setBootManagers(array $bootManagers): self
    {
        $this->bootManagers = $bootManagers;

        return $this;
    }

    /**
     * Add BootManager
     *
     * @param IRouterBootManager $bootManager
     * @return static
     */
    public function addBootManager(IRouterBootManager $bootManager): self
    {
        $this->bootManagers[] = $bootManager;

        return $this;
    }

    /**
     * Get routes that has been processed.
     *
     * @return array
     */
    public function getProcessedRoutes(): array
    {
        return $this->processedRoutes;
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Set routes
     *
     * @param array $routes
     * @return static
     */
    public function setRoutes(array $routes): self
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * Get current request
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get csrf verifier class
     * @return BaseCsrfVerifier
     */
    public function getCsrfVerifier(): ?BaseCsrfVerifier
    {
        return $this->csrfVerifier;
    }

    /**
     * Set csrf verifier class
     *
     * @param BaseCsrfVerifier $csrfVerifier
     */
    public function setCsrfVerifier(BaseCsrfVerifier $csrfVerifier): void
    {
        $this->csrfVerifier = $csrfVerifier;
    }

    /**
     * Set class loader
     *
     * @param IClassLoader $loader
     */
    public function setClassLoader(IClassLoader $loader): void
    {
        $this->classLoader = $loader;
    }

    /**
     * Get class loader
     *
     * @return IClassLoader
     */
    public function getClassLoader(): IClassLoader
    {
        return $this->classLoader;
    }

    /**
     * Register event handler
     *
     * @param IEventHandler $handler
     */
    public function addEventHandler(IEventHandler $handler): void
    {
        $this->eventHandlers[] = $handler;
    }

    /**
     * Get registered event-handler.
     *
     * @return array
     */
    public function getEventHandlers(): array
    {
        return $this->eventHandlers;
    }

    /**
     * Fire event in event-handler.
     *
     * @param string $name
     * @param array $arguments
     */
    protected function fireEvents(string $name, array $arguments = []): void
    {
        if (count($this->eventHandlers) === 0) {
            return;
        }

        /* @var IEventHandler $eventHandler */
        foreach ($this->eventHandlers as $eventHandler) {
            $eventHandler->fireEvents($this, $name, $arguments);
        }
    }

    /**
     * Add new debug message
     * @param string $message
     * @param array $args
     */
    public function debug(string $message, ...$args): void
    {
        if ($this->debugEnabled === false) {
            return;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $this->debugList[] = [
            'message' => vsprintf($message, $args),
            'time' => number_format(microtime(true) - $this->debugStartTime, 10),
            'trace' => end($trace),
        ];
    }

    /**
     * Enable or disables debugging
     *
     * @param bool $enabled
     * @return static
     */
    public function setDebugEnabled(bool $enabled): self
    {
        $this->debugEnabled = $enabled;

        return $this;
    }

    /**
     * Get the list containing all debug messages.
     *
     * @return array
     */
    public function getDebugLog(): array
    {
        return $this->debugList;
    }

    /**
     * Get the current processing route details.
     *
     * @return ILoadableRoute
     */
    public function getCurrentProcessingRoute(): ILoadableRoute
    {
        return $this->currentProcessingRoute;
    }

    /**
     * Changes the rendering behavior of the router.
     * When enabled the router will render all routes that matches.
     * When disabled the router will stop rendering at the first route that matches.
     *
     * @param bool $bool
     * @return $this
     */
    public function setRenderMultipleRoutes(bool $bool): self
    {
        $this->renderMultipleRoutes = $bool;

        return $this;
    }

    public function addExceptionHandler(IExceptionHandler $handler): self
    {
        $this->exceptionHandlers[] = $handler;

        return $this;
    }

}
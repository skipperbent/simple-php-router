<?php

namespace Pecee\SimpleRouter;

use Pecee\Exceptions\InvalidArgumentException;
use Pecee\Handlers\IExceptionHandler;
use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\HttpException;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
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
    protected $request;

    /**
     * Defines if a route is currently being processed.
     * @var bool
     */
    protected $processingRoute;

    /**
     * All added routes
     * @var array
     */
    protected $routes;

    /**
     * List of processed routes
     * @var array
     */
    protected $processedRoutes;

    /**
     * Stack of routes used to keep track of sub-routes added
     * when a route is being processed.
     * @var array
     */
    protected $routeStack;

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

    /**
     * List of loaded exception that has been loaded.
     * Used to ensure that exception-handlers aren't loaded twice when rewriting route.
     *
     * @var array
     */
    protected $loadedExceptionHandlers;

    /**
     * Enable or disabled debugging
     * @var bool
     */
    protected $debugEnabled = false;

    /**
     * The start time used when debugging is enabled
     * @var float
     */
    protected $debugStartTime;

    /**
     * List containing all debug messages
     * @var array
     */
    protected $debugList = [];

    /**
     * Router constructor.
     * @throws \Pecee\Http\Exceptions\MalformedUrlException
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * @throws \Pecee\Http\Exceptions\MalformedUrlException
     */
    public function reset(): void
    {
        $this->processingRoute = false;
        $this->request = new Request();
        $this->routes = [];
        $this->bootManagers = [];
        $this->routeStack = [];
        $this->processedRoutes = [];
        $this->exceptionHandlers = [];
        $this->loadedExceptionHandlers = [];
    }

    /**
     * Add route
     * @param IRoute $route
     * @return IRoute
     */
    public function addRoute(IRoute $route): IRoute
    {
        /*
         * If a route is currently being processed, that means that the route being added are rendered from the parent
         * routes callback, so we add them to the stack instead.
         */
        if ($this->processingRoute === true) {
            $this->routeStack[] = $route;

            return $route;
        }

        $this->routes[] = $route;

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

        $this->processingRoute = true;
        $route->renderRoute($this->request, $this);
        $this->processingRoute = false;

        if (\count($this->routeStack) !== 0) {

            /* Pop and grab the routes added when executing group callback earlier */
            $stack = $this->routeStack;
            $this->routeStack = [];

            /* Route any routes added to the stack */
            $this->processRoutes($stack, $route);
        }
    }

    /**
     * Process added routes.
     *
     * @param array $routes
     * @param IGroupRoute|null $group
     * @throws NotFoundHttpException
     */
    protected function processRoutes(array $routes, ?IGroupRoute $group = null): void
    {
        $this->debug('Processing routes');

        // Loop through each route-request
        $exceptionHandlers = [];

        // Stop processing routes if no valid route is found.
        if ($this->request->getRewriteRoute() === null && $this->request->getUrl() === null) {
            $this->debug('Halted route-processing as no valid route was found');

            return;
        }

        $url = $this->request->getRewriteUrl() ?? $this->request->getUrl()->getPath();

        /* @var $route IRoute */
        foreach ($routes as $route) {

            $this->debug('Processing route "%s"', \get_class($route));

            if ($group !== null) {
                /* Add the parent group */
                $route->setGroup($group);
            }

            /* @var $route IGroupRoute */
            if ($route instanceof IGroupRoute) {

                if ($route->matchRoute($url, $this->request) === true) {

                    /* Add exception handlers */
                    if (\count($route->getExceptionHandlers()) !== 0) {
                        /** @noinspection AdditionOperationOnArraysInspection */
                        $exceptionHandlers += $route->getExceptionHandlers();
                    }

                    /* Only render partial group if it matches */
                    if ($route instanceof IPartialGroupRoute === true) {
                        $this->renderAndProcess($route);
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

        $this->exceptionHandlers = array_merge($exceptionHandlers, $this->exceptionHandlers);
    }

    /**
     * Load routes
     * @throws NotFoundHttpException
     * @return void
     */
    public function loadRoutes(): void
    {
        $this->debug('Loading routes');

        /* Initialize boot-managers */
        /* @var $manager IRouterBootManager */
        foreach ($this->bootManagers as $manager) {
            $this->debug('Rendering bootmanager %s', \get_class($manager));
            $manager->boot($this->request);
            $this->debug('Finished rendering bootmanager');
        }

        /* Loop through each route-request */
        $this->processRoutes($this->routes);

        $this->debug('Finished loading routes');
    }

    /**
     * Routes the request
     *
     * @param bool $rewrite
     * @return string|null
     * @throws HttpException
     * @throws \Exception
     */
    public function routeRequest(bool $rewrite = false): ?string
    {
        $this->debug('Started routing request (rewrite: %s)', $rewrite === true ? 'yes' : 'no');

        $methodNotAllowed = false;

        try {

            if ($rewrite === false) {
                $this->loadRoutes();

                if ($this->csrfVerifier !== null) {

                    /* Verify csrf token for request */
                    $this->csrfVerifier->handle($this->request);
                }
            }

            $url = $this->request->getRewriteUrl() ?? $this->request->getUrl()->getPath();

            /* @var $route ILoadableRoute */
            foreach ($this->processedRoutes as $key => $route) {

                $this->debug('Matching route "%s"', \get_class($route));

                /* If the route matches */
                if ($route->matchRoute($url, $this->request) === true) {

                    /* Check if request method matches */
                    if (\count($route->getRequestMethods()) !== 0 && \in_array($this->request->getMethod(), $route->getRequestMethods(), true) === false) {
                        $this->debug('Method "%s" not allowed', $this->request->getMethod());
                        $methodNotAllowed = true;
                        continue;
                    }

                    $route->loadMiddleware($this->request, $this);

                    $output = $this->handleRouteRewrite($key, $url);
                    if ($output !== null) {
                        return $output;
                    }

                    /* Render route */
                    $methodNotAllowed = false;

                    $this->request->addLoadedRoute($route);

                    $output = $route->renderRoute($this->request, $this);

                    if ($output !== null) {
                        return $output;
                    }

                    $output = $this->handleRouteRewrite($key, $url);
                    if ($output !== null) {
                        return $output;
                    }
                }
            }

        } catch (\Exception $e) {
            $this->handleException($e);
        }

        if ($methodNotAllowed === true) {
            $message = sprintf('Route "%s" or method "%s" not allowed.', $this->request->getUrl()->getPath(), $this->request->getMethod());
            $this->handleException(new HttpException($message, 403));
        }

        if (\count($this->request->getLoadedRoutes()) === 0) {

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
     * @throws \Exception
     */
    protected function handleRouteRewrite($key, string $url): ?string
    {
        /* If the request has changed */
        if ($this->request->hasRewrite() === false) {
            return null;
        }

        $route = $this->request->getRewriteRoute();

        if ($route !== null) {
            /* Add rewrite route */
            $this->processedRoutes[] = $route;
        }

        if ($this->request->getRewriteUrl() !== $url) {
            unset($this->processedRoutes[$key]);
            $this->request->setHasRewrite(false);

            return $this->routeRequest(true);
        }

        return null;
    }

    /**
     * @param \Exception $e
     * @throws HttpException
     * @throws \Exception
     * @return string|null
     */
    protected function handleException(\Exception $e): ?string
    {
        $this->debug('Starting exception handling for "%s"', \get_class($e));

        /* @var $handler IExceptionHandler */
        foreach ($this->exceptionHandlers as $key => $handler) {

            if (\is_object($handler) === false) {
                $handler = new $handler();
            }

            $this->debug('Processing exception-handler "%s"', \get_class($handler));

            if (($handler instanceof IExceptionHandler) === false) {
                throw new HttpException('Exception handler must implement the IExceptionHandler interface.', 500);
            }

            try {

                $this->debug('Start rendering exception handler');
                $handler->handleError($this->request, $e);
                $this->debug('Finished rendering exception-handler');

                if (isset($this->loadedExceptionHandlers[$key]) === false && $this->request->hasRewrite() === true) {
                    $this->loadedExceptionHandlers[$key] = $handler;

                    $this->debug('Exception handler contains rewrite, reloading routes');

                    return $this->routeRequest(true);
                }

            } catch (\Exception $e) {

            }

            $this->debug('Finished processing');
        }

        $this->debug('Finished exception handling - exception not handled, throwing');
        throw $e;
    }

    public function arrayToParams(array $getParams = [], bool $includeEmpty = true): string
    {
        if (\count($getParams) !== 0) {

            if ($includeEmpty === false) {
                $getParams = array_filter($getParams, function ($item) {
                    return (trim($item) !== '');
                });
            }

            return '?' . http_build_query($getParams);
        }

        return '';
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

        /* @var $route ILoadableRoute */
        foreach ($this->processedRoutes as $route) {

            /* Check if the name matches with a name on the route. Should match either router alias or controller alias. */
            if ($route->hasName($name) === true) {
                $this->debug('Found route "%s" by name "%s"', $route->getUrl(), $name);

                return $route;
            }

            /* Direct match to controller */
            if ($route instanceof IControllerRoute && strtolower($route->getController()) === strtolower($name)) {
                $this->debug('Found route "%s" by controller "%s"', $route->getUrl(), $name);

                return $route;
            }

            /* Using @ is most definitely a controller@method or alias@method */
            if (\is_string($name) === true && strpos($name, '@') !== false) {
                [$controller, $method] = array_map('strtolower', explode('@', $name));

                if ($controller === strtolower($route->getClass()) && $method === strtolower($route->getMethod())) {
                    $this->debug('Found route "%s" by controller "%s" and method "%s"', $route->getUrl(), $controller, $method);

                    return $route;
                }
            }

            /* Check if callback matches (if it's not a function) */
            if (\is_string($name) === true && \is_string($route->getCallback()) && strpos($name, '@') !== false && strpos($route->getCallback(), '@') !== false && \is_callable($route->getCallback()) === false) {

                /* Check if the entire callback is matching */
                if (strpos($route->getCallback(), $name) === 0 || strtolower($route->getCallback()) === strtolower($name)) {
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
     * @throws InvalidArgumentException
     * @return string
     */
    public function getUrl(?string $name = null, $parameters = null, $getParams = null): string
    {
        $this->debug('Finding url', \func_get_args());

        if ($getParams !== null && \is_array($getParams) === false) {
            throw new InvalidArgumentException('Invalid type for getParams. Must be array or null');
        }

        if ($name === '' && $parameters === '') {
            return '/';
        }

        /* Only merge $_GET when all parameters are null */
        if ($name === null && $parameters === null && $getParams === null) {
            $getParams = $_GET;
        } else {
            $getParams = (array)$getParams;
        }

        /* Return current route if no options has been specified */
        if ($name === null && $parameters === null) {
            return $this->request->getUrl()->getPath() . $this->arrayToParams($getParams);
        }

        $loadedRoute = $this->request->getLoadedRoute();

        /* If nothing is defined and a route is loaded we use that */
        if ($name === null && $loadedRoute !== null) {
            return $loadedRoute->findUrl($loadedRoute->getMethod(), $parameters, $name) . $this->arrayToParams($getParams);
        }

        /* We try to find a match on the given name */
        $route = $this->findRoute($name);

        if ($route !== null) {
            return $route->findUrl($route->getMethod(), $parameters, $name) . $this->arrayToParams($getParams);
        }

        /* Using @ is most definitely a controller@method or alias@method */
        if (\is_string($name) === true && strpos($name, '@') !== false) {
            [$controller, $method] = explode('@', $name);

            /* Loop through all the routes to see if we can find a match */

            /* @var $route ILoadableRoute */
            foreach ($this->processedRoutes as $route) {

                /* Check if the route contains the name/alias */
                if ($route->hasName($controller) === true) {
                    return $route->findUrl($method, $parameters, $name) . $this->arrayToParams($getParams);
                }

                /* Check if the route controller is equal to the name */
                if ($route instanceof IControllerRoute && strtolower($route->getController()) === strtolower($controller)) {
                    return $route->findUrl($method, $parameters, $name) . $this->arrayToParams($getParams);
                }

            }
        }

        /* No result so we assume that someone is using a hardcoded url and join everything together. */
        $url = trim(implode('/', array_merge((array)$name, (array)$parameters)), '/');

        return (($url === '') ? '/' : '/' . $url . '/') . $this->arrayToParams($getParams);
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
     * @param array $bootManagers
     */
    public function setBootManagers(array $bootManagers): void
    {
        $this->bootManagers = $bootManagers;
    }

    /**
     * Add BootManager
     * @param IRouterBootManager $bootManager
     */
    public function addBootManager(IRouterBootManager $bootManager): void
    {
        $this->bootManagers[] = $bootManager;
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
     * @return static
     */
    public function setCsrfVerifier(BaseCsrfVerifier $csrfVerifier)
    {
        $this->csrfVerifier = $csrfVerifier;

        return $this;
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
            'time'    => number_format(microtime(true) - $this->debugStartTime, 10),
            'trace'   => end($trace),
        ];
    }

    /**
     * Enable or disables debugging
     *
     * @param bool $boolean
     */
    public function setDebugEnabled(bool $boolean): void
    {
        if ($boolean === true) {
            $this->debugStartTime = microtime(true);
        }

        $this->debugEnabled = $boolean;
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

}
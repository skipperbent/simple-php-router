<?php

namespace Pecee\SimpleRouter;

use Pecee\Http\Url;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Route\IRoute;
use Pecee\SimpleRouter\Route\IGroupRoute;
use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\SimpleRouter\Handlers\EventHandler;
use Pecee\SimpleRouter\Route\IControllerRoute;
use Pecee\SimpleRouter\Handlers\IEventHandler;
use Pecee\Exceptions\InvalidArgumentException;
use Pecee\SimpleRouter\ClassLoader\ClassLoader;
use Pecee\SimpleRouter\ClassLoader\IClassLoader;
use Pecee\Http\Exceptions\MalformedUrlException;
use Pecee\SimpleRouter\Exceptions\HttpException;
use Pecee\SimpleRouter\Route\IPartialGroupRoute;
use Pecee\SimpleRouter\Handlers\IExceptionHandler;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

/**
 * Class Router
 *
 * @package Pecee\SimpleRouter
 */
class Router
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var bool
     */
    protected $isProcessingRoute;

    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var array
     */
    protected $processedRoutes = [];

    /**
     * @var array
     */
    protected $routeStack = [];

    /**
     * @var array
     */
    protected $bootManagers = [];

    /**
     * @var BaseCsrfVerifier
     */
    protected $csrfVerifier;

    /**
     * @var array
     */
    protected $exceptionHandlers = [];

    /**
     * @var array
     */
    protected $loadedExceptionHandlers = [];

    /**
     * @var bool
     */
    protected $debugEnabled = false;

    /**
     * @var float
     */
    protected $debugStartTime;

    /**
     * @var array
     */
    protected $debugList = [];

    /**
     * @var array
     */
    protected $eventHandlers = [];

    /**
     * @var ClassLoader
     */
    protected $classLoader;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

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
     * @param IRoute $route
     * @return IRoute
     */
    public function addRoute(IRoute $route): IRoute
    {
        $this->fireEvents(EventHandler::EVENT_ADD_ROUTE, [
            'route' => $route,
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
     * @param IRoute $route
     * @throws NotFoundHttpException
     */
    protected function renderAndProcess(IRoute $route): void
    {

        $this->isProcessingRoute = true;
        $route->renderRoute($this->request, $this);
        $this->isProcessingRoute = false;

        if (\count($this->routeStack) !== 0) {
            /* Pop and grab the routes added when executing group callback earlier */
            $stack = $this->routeStack;
            $this->routeStack = [];

            /* Route any routes added to the stack */
            $this->processRoutes($stack, ($route instanceof IGroupRoute) ? $route : null);
        }
    }

    /**
     * @param array $routes
     * @param null|IGroupRoute $group
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
     * @throws NotFoundHttpException
     */
    public function loadRoutes(): void
    {
        $this->debug('Loading routes');

        $this->fireEvents(EventHandler::EVENT_BOOT, [
            'bootmanagers' => $this->bootManagers,
        ]);

        /* Initialize boot-managers */

        /* @var $manager IRouterBootManager */
        foreach ($this->bootManagers as $manager) {

            $className = \get_class($manager);
            $this->debug('Rendering bootmanager "%s"', $className);
            $this->fireEvents(EventHandler::EVENT_RENDER_BOOTMANAGER, [
                'bootmanagers' => $this->bootManagers,
                'bootmanager' => $manager,
            ]);

            /* Render bootmanager */
            $manager->boot($this, $this->request);

            $this->debug('Finished rendering bootmanager "%s"', $className);
        }

        $this->fireEvents(EventHandler::EVENT_LOAD_ROUTES, [
            'routes' => $this->routes,
        ]);

        /* Loop through each route-request */
        $this->processRoutes($this->routes);

        $this->debug('Finished loading routes');
    }

    /**
     * @return null|string
     * @throws HttpException
     * @throws NotFoundHttpException
     * @throws \Pecee\Http\Middleware\Exceptions\TokenMismatchException
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

            /* Verify csrf token for request */
            $this->csrfVerifier->handle($this->request);
        }

        $output = $this->routeRequest();

        $this->fireEvents(EventHandler::EVENT_LOAD, [
            'loadedRoutes' => $this->getRequest()->getLoadedRoutes(),
        ]);

        $this->debug('Routing complete');

        return $output;
    }

    /**
     * @return null|string
     * @throws HttpException
     */
    public function routeRequest(): ?string
    {
        $this->debug('Routing request');

        $methodNotAllowed = false;

        try {
            $url = $this->request->getRewriteUrl() ?? $this->request->getUrl()->getPath();

            /* @var $route ILoadableRoute */
            foreach ($this->processedRoutes as $key => $route) {

                $this->debug('Matching route "%s"', \get_class($route));

                /* If the route matches */
                if ($route->matchRoute($url, $this->request) === true) {

                    $this->fireEvents(EventHandler::EVENT_MATCH_ROUTE, [
                        'route' => $route,
                    ]);

                    /* Check if request method matches */
                    if (\count($route->getRequestMethods()) !== 0 && \in_array($this->request->getMethod(), $route->getRequestMethods(), true) === false) {
                        $this->debug('Method "%s" not allowed', $this->request->getMethod());
                        $methodNotAllowed = true;
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
            $this->handleException(new NotFoundHttpException($message, 403));
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
     * @param $key
     * @param string $url
     * @return null|string
     * @throws HttpException
     */
    protected function handleRouteRewrite($key, string $url): ?string
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
     * @param \Exception $e
     * @return null|string
     * @throws HttpException
     */
    protected function handleException(\Exception $e): ?string
    {
        $this->debug('Starting exception handling for "%s"', \get_class($e));

        $this->fireEvents(EventHandler::EVENT_LOAD_EXCEPTIONS, [
            'exception' => $e,
            'exceptionHandlers' => $this->exceptionHandlers,
        ]);

        /* @var $handler IExceptionHandler */
        foreach ($this->exceptionHandlers as $key => $handler) {

            if (\is_object($handler) === false) {
                $handler = new $handler();
            }

            $this->fireEvents(EventHandler::EVENT_RENDER_EXCEPTION, [
                'exception' => $e,
                'exceptionHandler' => $handler,
                'exceptionHandlers' => $this->exceptionHandlers,
            ]);

            $this->debug('Processing exception-handler "%s"', \get_class($handler));

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

            } catch (\Exception $e) {

            }

            $this->debug('Finished processing');
        }

        $this->debug('Finished exception handling - exception not handled, throwing');
        throw $e;
    }

    /**
     * @param string $name
     * @return null|ILoadableRoute
     */
    public function findRoute(string $name): ?ILoadableRoute
    {
        $this->debug('Finding route by name "%s"', $name);

        $this->fireEvents(EventHandler::EVENT_FIND_ROUTE, [
            'name' => $name,
        ]);

        /* @var $route ILoadableRoute */
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
            if (\is_string($name) === true && strpos($name, '@') !== false) {
                [$controller, $method] = array_map('strtolower', explode('@', $name));

                if ($controller === strtolower($route->getClass()) && $method === strtolower($route->getMethod())) {
                    $this->debug('Found route "%s" by controller "%s" and method "%s"', $route->getUrl(), $controller, $method);

                    return $route;
                }
            }

            /* Check if callback matches (if it's not a function) */
            $callback = $route->getCallback();
            if (\is_string($name) === true && \is_string($callback) === true && strpos($name, '@') !== false && strpos($callback, '@') !== false && \is_callable($callback) === false) {

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
     * @throws \Pecee\Http\Exceptions\MalformedUrlException
     */
    public function getUrl(?string $name = null, $parameters = null, ?array $getParams = null): Url
    {
        $this->debug('Finding url', \func_get_args());

        $this->fireEvents(EventHandler::EVENT_GET_URL, [
            'name' => $name,
            'parameters' => $parameters,
            'getParams' => $getParams,
        ]);

        if ($getParams !== null && \is_array($getParams) === false) {
            throw new InvalidArgumentException('Invalid type for getParams. Must be array or null');
        }

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
            return $this->request
                ->getUrlCopy()
                ->setPath($loadedRoute->findUrl($loadedRoute->getMethod(), $parameters, $name))
                ->setParams($getParams);
        }

        /* We try to find a match on the given name */
        $route = $this->findRoute($name);

        if ($route !== null) {
            return $this->request
                ->getUrlCopy()
                ->setPath($route->findUrl($route->getMethod(), $parameters, $name))
                ->setParams($getParams);
        }

        /* Using @ is most definitely a controller@method or alias@method */
        if (\is_string($name) === true && strpos($name, '@') !== false) {
            [$controller, $method] = explode('@', $name);

            /* Loop through all the routes to see if we can find a match */

            /* @var $route ILoadableRoute */
            foreach ($this->processedRoutes as $route) {

                /* Check if the route contains the name/alias */
                if ($route->hasName($controller) === true) {
                    return $this->request
                        ->getUrlCopy()
                        ->setPath($route->findUrl($method, $parameters, $name))
                        ->setParams($getParams);
                }

                /* Check if the route controller is equal to the name */
                if ($route instanceof IControllerRoute && strtolower($route->getController()) === strtolower($controller)) {
                    return $this->request
                        ->getUrlCopy()
                        ->setPath($route->findUrl($method, $parameters, $name))
                        ->setParams($getParams);
                }

            }
        }

        /* No result so we assume that someone is using a hardcoded url and join everything together. */
        $url = trim(implode('/', array_merge((array)$name, (array)$parameters)), '/');
        $url = (($url === '') ? '/' : '/' . $url . '/');

        return $this->request
            ->getUrlCopy()
            ->setPath($url)
            ->setParams($getParams);
    }

    /**
     * @return array
     */
    public function getBootManagers(): array
    {
        return $this->bootManagers;
    }

    /**
     * @param array $bootManagers
     * @return Router
     */
    public function setBootManagers(array $bootManagers): self
    {
        $this->bootManagers = $bootManagers;

        return $this;
    }

    /**
     * @param IRouterBootManager $bootManager
     * @return Router
     */
    public function addBootManager(IRouterBootManager $bootManager): self
    {
        $this->bootManagers[] = $bootManager;

        return $this;
    }

    /**
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
     * @param array $routes
     * @return Router
     */
    public function setRoutes(array $routes): self
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return null|BaseCsrfVerifier
     */
    public function getCsrfVerifier(): ?BaseCsrfVerifier
    {
        return $this->csrfVerifier;
    }

    /**
     * @param BaseCsrfVerifier $csrfVerifier
     */
    public function setCsrfVerifier(BaseCsrfVerifier $csrfVerifier): void
    {
        $this->csrfVerifier = $csrfVerifier;
    }

    /**
     * @param IClassLoader $loader
     */
    public function setClassLoader(IClassLoader $loader): void
    {
        $this->classLoader = $loader;
    }

    /**
     * @return IClassLoader
     */
    public function getClassLoader(): IClassLoader
    {
        return $this->classLoader;
    }

    /**
     * @param IEventHandler $handler
     */
    public function addEventHandler(IEventHandler $handler): void
    {
        $this->eventHandlers[] = $handler;
    }

    /**
     * @return array
     */
    public function getEventHandlers(): array
    {
        return $this->eventHandlers;
    }

    /**
     * @param $name
     * @param array $arguments
     */
    protected function fireEvents($name, array $arguments = []): void
    {
        if (\count($this->eventHandlers) === 0) {
            return;
        }

        /* @var IEventHandler $eventHandler */
        foreach ($this->eventHandlers as $eventHandler) {
            $eventHandler->fireEvents($this, $name, $arguments);
        }
    }

    /**
     * @param string $message
     * @param mixed ...$args
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
     * @param bool $enabled
     * @return Router
     */
    public function setDebugEnabled(bool $enabled): self
    {
        $this->debugEnabled = $enabled;

        return $this;
    }

    /**
     * @return array
     */
    public function getDebugLog(): array
    {
        return $this->debugList;
    }
}
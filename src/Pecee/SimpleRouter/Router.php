<?php
namespace Pecee\SimpleRouter;

use Pecee\Handlers\IExceptionHandler;
use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\HttpException;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\Route\IControllerRoute;
use Pecee\SimpleRouter\Route\IGroupRoute;
use Pecee\SimpleRouter\Route\ILoadableRoute;
use Pecee\SimpleRouter\Route\IRoute;

class Router
{

	/**
	 * The instance of this class
	 * @var static
	 */
	protected static $instance;

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
	 * The current loaded route
	 * @var ILoadableRoute|null
	 */
	protected $loadedRoute;

	/**
	 * List over route changes (to avoid endless-looping)
	 * @var array
	 */
	protected $routeRewrites = [];

	/**
	 * If the route has been rewritten/changed this property will contain the original url.
	 * @var string
	 */
	protected $originalUrl;

	/**
	 * Get current router instance
	 * @return static
	 */
	public static function getInstance()
	{
		if (static::$instance === null) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	public function __construct()
	{
		$this->reset();
	}

	public function reset()
	{
		$this->processingRoute = false;
		$this->request = new Request();
		$this->routes = [];
		$this->bootManagers = [];
		$this->routeStack = [];
		$this->processedRoutes = [];
		$this->exceptionHandlers = [];
	}

	/**
	 * Add route
	 * @param IRoute $route
	 * @return IRoute
	 */
	public function addRoute(IRoute $route)
	{
		/*
		 * If a route is currently being processed, that means that the
		 * route being added are rendered from the parent routes callback,
		 * so we add them to the stack instead.
		 */
		if ($this->processingRoute === true) {
			$this->routeStack[] = $route;
		} else {
			$this->routes[] = $route;
		}

		return $route;
	}

	protected function processRoutes(array $routes, IGroupRoute $group = null, IRoute $parent = null)
	{
		// Loop through each route-request
		/* @var $route IRoute */
		foreach ($routes as $route) {

			if ($route instanceof IGroupRoute) {

				$group = $route;

				if ($route->getCallback() !== null && is_callable($route->getCallback())) {

					$this->processingRoute = true;
					$route->renderRoute($this->request);
					$this->processingRoute = false;

					if ($route->matchRoute($this->request)) {

						/* Add exceptionhandlers */
						if (count($route->getExceptionHandlers()) > 0) {
							$this->exceptionHandlers = array_merge($route->getExceptionHandlers(), $this->exceptionHandlers);
						}

					}
				}
			}

			if ($group !== null) {

				/* Add the parent group */
				$route->setGroup($group);

			}

			if ($parent !== null) {

				/* Add the parent route */
				$route->setParent($parent);

				/* Add/merge parent settings with child */
				$route->setSettings($parent->toArray(), true);

			}

			if ($route instanceof ILoadableRoute) {

				/* Add the route to the map, so we can find the active one when all routes has been loaded */
				$this->processedRoutes[] = $route;
			}

			if (count($this->routeStack) > 0) {

				/* Pop and grap the routes added when executing group callback earlier */
				$stack = $this->routeStack;
				$this->routeStack = [];

				/* Route any routes added to the stack */
				$this->processRoutes($stack, $route, $group);
			}
		}
	}

	public function routeRequest($rewrite = false)
	{
		$this->loadedRoute = null;
		$routeNotAllowed = false;

		try {

			/* Initialize boot-managers */
			if (count($this->bootManagers) > 0) {
				/* @var $manager IRouterBootManager */
				foreach ($this->bootManagers as $manager) {
					$this->request = $manager->boot($this->request);

					if (!($this->request instanceof Request)) {
						throw new HttpException('Bootmanager "' . get_class($manager) . '" must return instance of ' . Request::class, 500);
					}
				}
			}

			if ($rewrite === false) {

				/* Loop through each route-request */
				$this->processRoutes($this->routes);

				if ($this->csrfVerifier !== null) {

					// Verify csrf token for request
					$this->csrfVerifier->handle($this->request);
				}

				$this->originalUrl = $this->request->getUri();
			}

			/* @var $route IRoute */
			foreach ($this->processedRoutes as $route) {

				/* If the route matches */
				if ($route->matchRoute($this->request)) {

					/* Check if request method matches */
					if (count($route->getRequestMethods()) > 0 && !in_array($this->request->getMethod(), $route->getRequestMethods())) {
						$routeNotAllowed = true;
						continue;
					}

					$this->loadedRoute = $route;
					$this->loadedRoute->loadMiddleware($this->request, $this->loadedRoute);

					/* If the request has changed, we reinitialize the router */
					if ($this->request->getUri() !== $this->originalUrl && !in_array($this->request->getUri(), $this->routeRewrites)) {
						$this->routeRewrites[] = $this->request->getUri();
						$this->routeRequest(true);

						return;
					}

					/* Render route */
					$routeNotAllowed = false;
					$this->request->setUri($this->originalUrl);
					$this->loadedRoute->renderRoute($this->request);

					break;
				}
			}

		} catch (\Exception $e) {
			$this->handleException($e);
		}

		if ($routeNotAllowed === true) {
			$this->handleException(new HttpException('Route or method not allowed', 403));
		}

		if ($this->loadedRoute === null) {
			$this->handleException(new NotFoundHttpException('Route not found: ' . $this->request->getUri(), 404));
		}
	}

	protected function handleException(\Exception $e)
	{
		/* @var $handler IExceptionHandler */
		foreach ($this->exceptionHandlers as $handler) {

			$handler = new $handler();

			if (!($handler instanceof IExceptionHandler)) {
				throw new HttpException('Exception handler must implement the IExceptionHandler interface.', 500);
			}

			$request = $handler->handleError($this->request, $this->loadedRoute, $e);

			/* If the request has changed */
			if ($request !== null && $this->request->getUri() !== $this->originalUrl && !in_array($request->getUri(), $this->routeRewrites)) {
				$this->request = $request;
				$this->routeRewrites[] = $request->getUri();
				$this->routeRequest(true);

				return;
			}
		}

		throw $e;
	}

	public function arrayToParams(array $getParams = [], $includeEmpty = true)
	{
		if (count($getParams) > 0) {

			if ($includeEmpty === false) {
				$getParams = array_filter($getParams, function ($item) {
					return (!empty($item));
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
	public function findRoute($name)
	{
		/* @var $route ILoadableRoute */
		foreach ($this->processedRoutes as $route) {

			/* Check if the name matches with a name on the route. Should match either router alias or controller alias. */
			if ($route->hasName($name)) {
				return $route;
			}

			/* Direct match to controller */
			if ($route instanceof IControllerRoute && strtolower($route->getController()) === strtolower($name)) {
				return $route;
			}

			/* Using @ is most definitely a controller@method or alias@method */
			if (strpos($name, '@') !== false) {
				list($controller, $method) = array_map('strtolower', explode('@', $name));

				if ($controller === strtolower($route->getClass()) && $method === strtolower($route->getMethod())) {
					return $route;
				}
			}

			/* Check if callback matches (if it's not a function) */
			if (strpos($name, '@') !== false && strpos($route->getCallback(), '@') !== false && !is_callable($route->getCallback())) {

				/* Check if the entire callback is matching */
				if (strtolower($route->getCallback()) === strtolower($name) || strpos($route->getCallback(), $name) === 0) {
					return $route;
				}

				/* Check if the class part of the callback matches (class@method) */
				if (strtolower($name) === strtolower($route->getClass())) {
					return $route;
				}
			}
		}

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
	 * @return string
	 */
	public function getUrl($name = null, $parameters = null, array $getParams = null)
	{
		if ($getParams !== null && is_array($getParams) === false) {
			throw new \InvalidArgumentException('Invalid type for getParams. Must be array or null');
		}

		if ($getParams === null) {
			$getParams = $_GET;
		}

		/* Return current route if no options has been specified */
		if ($name === null && $parameters === null) {
			return '/' . trim(parse_url($this->request->getUri(), PHP_URL_PATH), '/') . '/' . $this->arrayToParams($getParams);
		}

		/* If nothing is defined and a route is loaded we use that */
		if ($name === null && $this->loadedRoute !== null) {
			return $this->loadedRoute->findUrl($this->loadedRoute->getMethod(), $parameters, $name) . $this->arrayToParams($getParams);
		}

		/* We try to find a match on the given name */
		$route = $this->findRoute($name);

		if ($route !== null) {
			return $route->findUrl($route->getMethod(), $parameters, $name) . $this->arrayToParams($getParams);
		}

		/* Using @ is most definitely a controller@method or alias@method */
		if (stripos($name, '@') !== false) {
			list($controller, $method) = explode('@', $name);

			/* Loop through all the routes to see if we can find a match */

			/* @var $route ILoadableRoute */
			foreach ($this->processedRoutes as $route) {

				/* Check if the route contains the name/alias */
				if ($route->hasName($controller)) {
					return $route->findUrl($method, $parameters, $name) . $this->arrayToParams($getParams);
				}

				/* Check if the route controller is equal to the name */
				if ($route instanceof IControllerRoute && strtolower($route->getController()) === strtolower($controller)) {
					return $route->findUrl($method, $parameters, $name) . $this->arrayToParams($getParams);
				}

			}
		}

		/* No result so we assume that someone is using a hardcoded url and join everything together. */

		return '/' . trim(join('/', array_merge((array)$name, (array)$parameters)), '/') . '/' . $this->arrayToParams($getParams);
	}

	/**
	 * Get bootmanagers
	 * @return array
	 */
	public function getBootManagers()
	{
		return $this->bootManagers;
	}

	/**
	 * Set bootmanagers
	 * @param array $bootManagers
	 */
	public function setBootManagers(array $bootManagers)
	{
		$this->bootManagers = $bootManagers;
	}

	/**
	 * Add bootmanager
	 * @param IRouterBootManager $bootManager
	 */
	public function addBootManager(IRouterBootManager $bootManager)
	{
		$this->bootManagers[] = $bootManager;
	}

	/**
	 * @return array
	 */
	public function getRoutes()
	{
		return $this->routes;
	}

	/**
	 * Get current request
	 *
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Get csrf verifier class
	 * @return BaseCsrfVerifier
	 */
	public function getCsrfVerifier()
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
	 * Get loaded route
	 * @return ILoadableRoute|null
	 */
	public function getLoadedRoute()
	{
		return $this->loadedRoute;
	}

}
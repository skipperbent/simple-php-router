<?php
namespace Pecee\SimpleRouter;

use Pecee\Exception\RouterException;
use Pecee\Handler\IExceptionHandler;
use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\Http\Request;
use Pecee\Http\Response;

class RouterBase
{

	/**
	 * @var static
	 */
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
	 * Used to keep track of whether or not a should should be added to
	 * the backstack-list for group-processing or not.
	 * @var bool
	 */
	protected $processingRoute;

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
	 * @var RouterRoute|null
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
		$this->response = new Response($this->request);
		$this->routes = array();
		$this->bootManagers = array();
		$this->backStack = array();
		$this->controllerUrlMap = array();
		$this->exceptionHandlers = array();
	}

	/**
	 * Add route
	 * @param RouterEntry $route
	 * @return RouterEntry
	 */
	public function addRoute(RouterEntry $route)
	{
		if ($this->processingRoute) {
			$this->backStack[] = $route;
		} else {
			$this->routes[] = $route;
		}

		return $route;
	}

	protected function processRoutes(array $routes, array $settings = array(), array $prefixes = array(), RouterEntry $parent = null)
	{
		// Loop through each route-request
		/* @var $route RouterEntry */
		foreach ($routes as $route) {

			$newPrefixes = $prefixes;
			$newSettings = $settings;

			if ($parent !== null) {
				$route->setParent($parent);
			}

			if (count($settings)) {
				$route->merge($settings);
			}

			if ($route instanceof RouterGroup) {

				if ($route->getCallback() !== null && is_callable($route->getCallback())) {

					$this->processingRoute = true;
					$route->renderRoute($this->request);
					$this->processingRoute = false;

					if ($route->matchRoute($this->request)) {
						// Add ExceptionHandler
						if (count($route->getExceptionHandlers()) > 0) {
							$this->exceptionHandlers = array_merge($route->getExceptionHandlers(), $this->exceptionHandlers);
						}
					}
				}

				$newPrefixes[] = trim($route->getPrefix(), '/');
				$newSettings = array_merge($settings, $route->toArray());

			}

			if ($route instanceof ILoadableRoute) {

				if (count($prefixes)) {
					$route->setUrl(trim(join('/', $prefixes) . $route->getUrl(), '/'));
				}

				$this->controllerUrlMap[] = $route;
			}

			if (count($this->backStack) > 0) {
				$backStack = $this->backStack;
				$this->backStack = [];

				// Route any routes added to the backstack
				$this->processRoutes($backStack, $newSettings, $newPrefixes, $route);
			}
		}
	}

	public function routeRequest($rewrite = false)
	{
		$this->loadedRoute = null;
		$routeNotAllowed = false;

		try {

			// Initialize boot-managers
			if (count($this->bootManagers) > 0) {
				/* @var $manager RouterBootManager */
				foreach ($this->bootManagers as $manager) {
					$this->request = $manager->boot($this->request);

					if (!($this->request instanceof Request)) {
						throw new RouterException('Custom router bootmanager "' . get_class($manager) . '" must return instance of Request.');
					}
				}
			}

			if ($rewrite === false) {

				// Loop through each route-request
				$this->processRoutes($this->routes);

				if ($this->csrfVerifier !== null) {

					// Verify csrf token for request
					$this->csrfVerifier->handle($this->request);
				}

				$this->originalUrl = $this->request->getUri();
			}

			/* @var $route RouterEntry */
			foreach ($this->controllerUrlMap as $route) {

				if ($route->matchRoute($this->request)) {

					if (count($route->getRequestMethods()) > 0 && !in_array($this->request->getMethod(), $route->getRequestMethods())) {
						$routeNotAllowed = true;
						continue;
					}

					$this->loadedRoute = $route;
					$this->loadedRoute->loadMiddleware($this->request, $this->loadedRoute);

					if ($this->request->getUri() !== $this->originalUrl && !in_array($this->request->getUri(), $this->routeRewrites)) {
						$this->routeRewrites[] = $this->request->getUri();
						$this->routeRequest(true);
						return;
					}

					$routeNotAllowed = false;
					$this->request->setUri($this->originalUrl);
					$this->loadedRoute->renderRoute($this->request);

					break;
				}
			}

		} catch (\Exception $e) {
			$this->handleException($e);
		}

		if ($routeNotAllowed) {
			$this->handleException(new RouterException('Route or method not allowed', 403));
		}

		if ($this->loadedRoute === null) {
			$this->handleException(new RouterException(sprintf('Route not found: %s', $this->request->getUri()), 404));
		}
	}

	protected function handleException(\Exception $e)
	{
		/* @var $handler IExceptionHandler */
		foreach ($this->exceptionHandlers as $handler) {

			$handler = new $handler();

			if (!($handler instanceof IExceptionHandler)) {
				throw new RouterException('Exception handler must implement the IExceptionHandler interface.');
			}

			$request = $handler->handleError($this->request, $this->loadedRoute, $e);

			if ($request !== null && $request->getUri() !== $this->originalUrl && !in_array($request->getUri(), $this->routeRewrites)) {
				$this->routeRewrites[] = $request->getUri();
				$this->routeRequest(true);
				return;
			}

		}

		throw $e;
	}

	public function arrayToParams(array $getParams = null, $includeEmpty = true)
	{
		if (is_array($getParams) === true && count($getParams) > 0) {

			if ($includeEmpty === false) {
				$getParams = array_filter($getParams, function ($item) {
					return (!empty($item));
				});
			}

			return '?' . http_build_query($getParams);
		}

		return '';
	}

	protected function processUrl(LoadableRoute $route, $method = null, $parameters = null, $getParams = null)
	{
		$domain = '';
		$parent = $route->getParent();

		$parameters = (array)$parameters;

		if ($parent !== null && $parent instanceof RouterGroup && count($parent->getDomains()) > 0) {
			$domain = $parent->getDomains();
			$domain = '//' . $domain[0];
		}

		$url = $domain . '/' . trim($route->getUrl(), '/');

		if ($route instanceof IControllerRoute && $method !== null) {

			$url .= '/' . $method . '/';

			if (count($parameters) > 0) {
				$url .= join('/', (array)$parameters);
			}

		} else {

			if ($parameters !== null && count($parameters) > 0) {
				$params = array_merge($route->getParameters(), (array)$parameters);
			} else {
				$params = $route->getParameters();
			}

			$otherParams = array();

			foreach ($params as $param => $value) {
				$value = (isset($parameters[$param])) ? $parameters[$param] : $value;

				$param1 = LoadableRoute::PARAMETER_MODIFIERS[0] . $param . LoadableRoute::PARAMETER_MODIFIERS[1];
				$param2 = LoadableRoute::PARAMETER_MODIFIERS[0] . $param . LoadableRoute::PARAMETER_OPTIONAL_SYMBOL . LoadableRoute::PARAMETER_MODIFIERS[1];

				if (stripos($url, $param1) !== false || stripos($url, $param) !== false) {
					$url = str_ireplace([$param1, $param2], $value, $url);
				} else {
					$otherParams[$param] = $value;
				}
			}

			$url = rtrim($url, '/') . '/' . join('/', $otherParams);
		}

		$url = rtrim($url, '/') . '/';

		if ($getParams !== null) {
			$url .= $this->arrayToParams($getParams);
		}

		return $url;
	}

	/**
	 * Find route by alias, class, callback or method.
	 *
	 * @param string $query
	 * @return LoadableRoute|null
	 */
	public function findRoute($query)
	{
		/* @var $route LoadableRoute */
		foreach ($this->controllerUrlMap as $route) {

			// Check an alias exist, if the matches - use it
			// Matches either Router alias or controller alias.
			if ($route->hasAlias($query)) {
				return $route;
			}

			// Direct match to controller
			if ($route instanceof IControllerRoute) {
				if (strtolower($route->getController()) === strtolower($query)) {
					return $route;
				}
			}

			// Using @ is most definitely a controller@method or alias@method
			if (strpos($query, '@') !== false) {
				list($controller, $method) = array_map('strtolower', explode('@', $query));

				if ($controller === strtolower($route->getClass()) && $method === strtolower($route->getMethod())) {
					return $route;
				}
			}

			// Use callback if it's not a function
			if (strpos($query, '@') !== false && strpos($route->getCallback(), '@') !== false && !is_callable($route->getCallback())) {

				if (strtolower($query) === strtolower($route->getClass())) {
					return $route;
				}

				if (strtolower($route->getCallback()) === strtolower($query) || strpos($route->getCallback(), $query) === 0) {
					return $route;
				}

			}
		}

		return null;
	}

	public function getRoute($controller = null, $parameters = null, $getParams = null)
	{
		if ($getParams !== null && is_array($getParams) === false) {
			throw new \InvalidArgumentException('Invalid type for getParams. Must be array or null');
		}

		// Return current route if no options has been specified
		if ($controller === null && $parameters === null) {

			$getParams = ($getParams !== null) ? $getParams : $_GET;
			$url = parse_url($this->request->getUri(), PHP_URL_PATH) . $this->arrayToParams($getParams);

			return $url;
		}

		// If nothing is defined and a route is loaded we use that
		if ($controller === null && $this->loadedRoute !== null) {
			return $this->processUrl($this->loadedRoute, $this->loadedRoute->getMethod(), $parameters, $getParams);
		}

		$route = $this->findRoute($controller);

		if ($route !== null) {
			return $this->processUrl($route, $route->getMethod(), $parameters, $getParams);
		}

		// Using @ is most definitely a controller@method or alias@method
		if (stripos($controller, '@') !== false) {
			list($controller, $method) = explode('@', $controller);

			/* @var $route LoadableRoute */
			foreach ($this->controllerUrlMap as $route) {

				if ($route->hasAlias($controller)) {
					return $this->processUrl($route, $method, $parameters, $getParams);
				}

				// Match controllers either by: "alias @ method" or "controller@method"
				if ($route instanceof IControllerRoute && strtolower($route->getController()) === strtolower($controller)) {
					return $this->processUrl($route, $method, $parameters, $getParams);
				}

			}
		}

		$url = [($controller === null) ? '/' : $controller];

		if ($parameters !== null && count($parameters) > 0) {
			$url = array_merge($url, (array)$parameters);
		}

		$url = '/' . trim(join('/', $url), '/') . '/';

		if ($getParams !== null) {
			$url .= $this->arrayToParams($getParams);
		}

		return $url;
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
	 * @param RouterBootManager $bootManager
	 */
	public function addBootManager(RouterBootManager $bootManager)
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
	 * Get response
	 * @return Response
	 */
	public function getResponse()
	{
		return $this->response;
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
	 * @return RouterRoute|null
	 */
	public function getLoadedRoute()
	{
		return $this->loadedRoute;
	}

}
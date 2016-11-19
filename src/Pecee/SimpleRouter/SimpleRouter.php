<?php
/**
 * ---------------------------
 * Router helper class
 * ---------------------------
 * This class is added so calls can be made statically like Router::get() making the code look more pretty.
 */
namespace Pecee\SimpleRouter;

use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\SimpleRouter\Exceptions\HttpException;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

class SimpleRouter
{
	protected static $defaultNamespace;

	/**
	 * Start/route request
	 *
	 * @throws HttpException
	 * @throws NotFoundHttpException
	 */
	public static function start()
	{
		static::router()->routeRequest();
	}

	/**
	 * Set default namespace which will be prepended to all routes.
	 *
	 * @param string $defaultNamespace
	 */
	public static function setDefaultNamespace($defaultNamespace)
	{
		static::$defaultNamespace = $defaultNamespace;
	}

	/**
	 * Base CSRF verifier
	 *
	 * @param BaseCsrfVerifier $baseCsrfVerifier
	 */
	public static function csrfVerifier(BaseCsrfVerifier $baseCsrfVerifier)
	{
		static::router()->setCsrfVerifier($baseCsrfVerifier);
	}

	/**
	 * Boot managers allows you to alter the routes before the routing occurs.
	 * Perfect if you want to load pretty-urls from a file or database.
	 *
	 * @param RouterBootManager $bootManager
	 */
	public static function addBootManager(RouterBootManager $bootManager)
	{
		static::router()->addBootManager($bootManager);
	}

	/**
	 * Route the given url to your callback on GET request method.
	 *
	 * @param string $url
	 * @param string|\Closure $callback
	 * @param array|null $settings
	 * @return RouterRoute
	 */
	public static function get($url, $callback, array $settings = null)
	{
		return static::match(['get'], $url, $callback, $settings);
	}

	/**
	 * Route the given url to your callback on POST request method.
	 *
	 * @param string $url
	 * @param string|\Closure $callback
	 * @param array|null $settings
	 * @return RouterRoute
	 */
	public static function post($url, $callback, array $settings = null)
	{
		return static::match(['post'], $url, $callback, $settings);
	}

	/**
	 * Route the given url to your callback on PUT request method.
	 *
	 * @param string $url
	 * @param string|\Closure $callback
	 * @param array|null $settings
	 * @return RouterRoute
	 */
	public static function put($url, $callback, array $settings = null)
	{
		return static::match(['put'], $url, $callback, $settings);
	}

	/**
	 * Route the given url to your callback on PATCH request method.
	 *
	 * @param string $url
	 * @param string|\Closure $callback
	 * @param array|null $settings
	 * @return RouterRoute
	 */
	public static function patch($url, $callback, array $settings = null)
	{
		return static::match(['patch'], $url, $callback, $settings);
	}

	/**
	 * Route the given url to your callback on OPTIONS request method.
	 *
	 * @param string $url
	 * @param string|\Closure $callback
	 * @param array|null $settings
	 * @return RouterRoute
	 */
	public static function options($url, $callback, array $settings = null)
	{
		return static::match(['options'], $url, $callback, $settings);
	}

	/**
	 * Route the given url to your callback on DELETE request method.
	 *
	 * @param string $url
	 * @param string|\Closure $callback
	 * @param array|null $settings
	 * @return RouterRoute
	 */
	public static function delete($url, $callback, array $settings = null)
	{
		return static::match(['delete'], $url, $callback, $settings);
	}

	/**
	 * Groups allows for encapsulating routes with special settings.
	 *
	 * @param array $settings
	 * @param \Closure $callback
	 * @throws \InvalidArgumentException
	 * @return RouterGroup
	 */
	public static function group(array $settings = [], \Closure $callback)
	{
		$group = new RouterGroup();
		$group->setCallback($callback);
		$group->merge($settings);

		if (is_callable($callback) === false) {
			throw new \InvalidArgumentException('Invalid callback provided. Only functions or methods supported');
		}

		static::router()->addRoute($group);

		return $group;
	}

	/**
	 * Alias for the form method
	 *
	 * @param string $url
	 * @param callable $callback
	 * @param array|null $settings
	 * @see SimpleRouter::form
	 * @return RouterRoute
	 */
	public static function basic($url, $callback, array $settings = null)
	{
		return static::match(['get', 'post'], $url, $callback, $settings);
	}

	/**
	 * This type will route the given url to your callback on the provided request methods.
	 * Route the given url to your callback on POST and GET request method.
	 *
	 * @param string $url
	 * @param string|\Closure $callback
	 * @param array|null $settings
	 * @see SimpleRouter::form
	 * @return RouterRoute
	 */
	public static function form($url, $callback, array $settings = null)
	{
		return static::match(['get', 'post'], $url, $callback, $settings);
	}

	/**
	 * This type will route the given url to your callback on the provided request methods.
	 *
	 * @param array $requestMethods
	 * @param string $url
	 * @param string|\Closure $callback
	 * @param array|null $settings
	 * @return RouterEntry|RouterRoute
	 */
	public static function match(array $requestMethods, $url, $callback, array $settings = null)
	{
		$route = new RouterRoute($url, $callback);
		$route->setRequestMethods($requestMethods);
		$route = static::addDefaultNamespace($route);

		if ($settings !== null) {
			$route->merge($settings);
		}

		static::router()->addRoute($route);

		return $route;
	}

	/**
	 * This type will route the given url to your callback and allow any type of request method
	 *
	 * @param string $url
	 * @param string|\Closure $callback
	 * @param array|null $settings
	 * @return RouterRoute
	 */
	public static function all($url, $callback, array $settings = null)
	{
		$route = new RouterRoute($url, $callback);

		$route = static::addDefaultNamespace($route);

		if ($settings !== null) {
			$route->merge($settings);
		}

		static::router()->addRoute($route);

		return $route;
	}

	/**
	 * This route will route request from the given url to the controller.
	 *
	 * @param string $url
	 * @param string $controller
	 * @param array|null $settings
	 * @return RouterController
	 */
	public static function controller($url, $controller, array $settings = null)
	{
		$route = new RouterController($url, $controller);

		$route = static::addDefaultNamespace($route);

		if ($settings !== null) {
			$route->merge($settings);
		}

		static::router()->addRoute($route);

		return $route;
	}

	/**
	 * This type will route all REST-supported requests to different methods in the provided controller.
	 *
	 * @param string $url
	 * @param string $controller
	 * @param array|null $settings
	 * @return RouterResource
	 */
	public static function resource($url, $controller, array $settings = null)
	{
		$route = new RouterResource($url, $controller);

		if ($settings !== null) {
			$route->merge($settings);
		}

		static::router()->addRoute($route);

		return $route;
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
	 * This method is an alias for SimpleRouter::getUrl().
	 *
	 * @see SimpleRouter::getUrl()
	 * @param string|null $name
	 * @param string|array|null $parameters
	 * @param array|null $getParams
	 * @return string
	 */
	public static function getRoute($name = null, $parameters = null, array $getParams = [])
	{
		return static::getUrl($name, $parameters, $getParams);
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
	public static function getUrl($name = null, $parameters = null, array $getParams = [])
	{
		return static::router()->getUrl($name, $parameters, $getParams);
	}

	/**
	 * Get the request
	 *
	 * @return \Pecee\Http\Request
	 */
	public static function request()
	{
		return static::router()->getRequest();
	}

	/**
	 * Get the response object
	 *
	 * @return \Pecee\Http\Response
	 */
	public static function response()
	{
		return static::router()->getResponse();
	}

	/**
	 * Returns the router instance
	 *
	 * @return RouterBase
	 */
	public static function router()
	{
		return RouterBase::getInstance();
	}

	/**
	 * Prepends the default namespace to all new routes added.
	 *
	 * @param RouterEntry $route
	 * @return RouterEntry
	 */
	protected static function addDefaultNamespace(RouterEntry $route)
	{
		if (static::$defaultNamespace !== null) {
			$namespace = static::$defaultNamespace;

			if ($route->getNamespace() !== null) {
				$namespace .= '\\' . $route->getNamespace();
			}

			$route->setDefaultNamespace($namespace);
		}

		return $route;
	}

}
<?php
namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\HttpException;

abstract class LoadableRoute extends Route implements ILoadableRoute
{
	const PARAMETERS_REGEX_MATCH = '%s([\w\-\_]*?)\%s{0,1}%s';

	protected $url;
	protected $name;
	protected $middlewares = [];

	public function loadMiddleware(Request $request, ILoadableRoute &$route)
	{
		if (count($this->getMiddlewares()) > 0) {
			foreach ($this->getMiddlewares() as $middleware) {

				$middleware = $this->loadClass($middleware);
				if (!($middleware instanceof IMiddleware)) {
					throw new HttpException($middleware . ' must be instance of Middleware');
				}

				$middleware->handle($request, $route);
			}
		}
	}

	/**
	 * Set url
	 *
	 * @param string $url
	 * @return static
	 */
	public function setUrl($url)
	{
		$this->url = ($url === '/') ? '/' : '/' . trim($url, '/') . '/';
		$regex = sprintf(static::PARAMETERS_REGEX_MATCH, $this->paramModifiers[0], $this->paramOptionalSymbol, $this->paramModifiers[1]);

		if (preg_match_all('/' . $regex . '/is', $this->url, $matches)) {
			foreach ($matches[1] as $key) {
				$this->parameters[$key] = null;
			}
		}

		return $this;
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function findUrl($method = null, $parameters = null, $name = null)
	{
		$url = '';

		$parameters = (array)$parameters;

		if ($this->getGroup() !== null && count($this->getGroup()->getDomains()) > 0) {
			$url .= '//' . $this->getGroup()->getDomains()[0];
		}

		$url .= $this->getUrl();

		$params = array_merge($this->getParameters(), $parameters);

		/* Url that contains parameters that aren't recognized */
		$unknownParams = [];

		/* Create the param string - {} */
		$param1 = $this->paramModifiers[0] . '%s' . $this->paramModifiers[1];

		/* Create the param string with the optional symbol - {?} */
		$param2 = $this->paramModifiers[0] . '%s' . $this->paramOptionalSymbol . $this->paramModifiers[1];

		/* Let's parse the values of any {} parameter in the url */
		foreach ($params as $param => $value) {
			$value = (isset($parameters[$param])) ? $parameters[$param] : $value;

			if (stripos($url, $param1) !== false || stripos($url, $param) !== false) {
				$url = str_ireplace([sprintf($param1, $param), sprintf($param2, $param)], $value, $url);
			} else {
				$unknownParams[$param] = $value;
			}
		}

		$url .= '/' . join('/', $unknownParams);

		return rtrim($url, '/') . '/';
	}

	/**
	 * Returns the provided name for the router (first if multiple).
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Check if route has given name.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasName($name)
	{
		return (strtolower($this->name) === strtolower($name));
	}

	/**
	 * Sets the router name, which makes it easier to obtain the url or router at a later point.
	 * Alias for LoadableRoute::setName().
	 *
	 * @see LoadableRoute::setName()
	 * @param string|array $name
	 * @return static
	 */
	public function name($name)
	{
		return $this->setName($name);
	}

	/**
	 * Sets the router name, which makes it easier to obtain the url or router at a later point.
	 *
	 * @param string $name
	 * @return static $this
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Export route settings to array so they can be merged with another route.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$values = [];

		if (count($this->middlewares) > 0) {
			$values['middleware'] = $this->middlewares;
		}

		return array_merge(parent::toArray(), $values);
	}

	/**
	 * Merge with information from another route.
	 *
	 * @param array $values
	 * @param bool $merge
	 * @return static
	 */
	public function setSettings(array $values, $merge = false)
	{
		if (isset($values['as'])) {
			if ($this->name !== null && $merge !== false) {
				$this->setName($values['as'] . '.' . $this->name);
			} else {
				$this->setName($values['as']);
			}
		}

		if (isset($values['prefix'])) {
			$this->setUrl($values['prefix'] . $this->getUrl());
		}

		// Push middleware if multiple
		if (isset($values['middleware'])) {
			$this->setMiddlewares(array_merge((array)$values['middleware'], $this->middlewares));
		}

		parent::setSettings($values, $merge);

		return $this;
	}

	/**
	 * @param string $middleware
	 * @return static
	 */
	public function setMiddleware($middleware)
	{
		$this->middlewares[] = $middleware;

		return $this;
	}

	public function setMiddlewares(array $middlewares)
	{
		$this->middlewares = $middlewares;

		return $this;
	}

	/**
	 * @return string|array
	 */
	public function getMiddlewares()
	{
		return $this->middlewares;
	}

}
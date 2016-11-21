<?php
namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

class RouteController extends LoadableRoute implements IControllerRoute
{
	protected $defaultMethod = 'index';
	protected $controller;
	protected $method;
	protected $names = [];

	public function __construct($url, $controller)
	{
		$this->setUrl($url);
		$this->setName(trim(str_replace('/', '.', $url), '/'));
		$this->controller = $controller;
	}

	/**
	 * Check if route has given name.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasName($name)
	{
		if ($this->name === null) {
			return false;
		}

		/* Remove method/type */
		if (stripos($name, '.') !== false) {
			$method = substr($name, strrpos($name, '.') + 1);
			$newName = substr($name, 0, strrpos($name, '.'));

			if (strtolower($this->name) === strtolower($newName) && in_array($method, $this->names)) {
				return true;
			}
		}

		return parent::hasName($name);
	}

	public function findUrl($method = null, $parameters = null, $name = null)
	{

		if (stripos($name, '.') !== false) {
			$found = array_search(substr($name, strrpos($name, '.') + 1), $this->names);
			if ($found !== false) {
				$method = $found;
			}
		}

		$url = '';

		$parameters = (array)$parameters;

		/* Remove requestType from method-name, if it exists */
		if ($method !== null) {
			foreach (static::$requestTypes as $requestType) {
				if (stripos($method, $requestType) === 0) {
					$method = substr($method, strlen($requestType));
					break;
				}
			}
			$method .= '/';
		}

		if ($this->getGroup() !== null && count($this->getGroup()->getDomains()) > 0) {
			$url .= '//' . $this->getGroup()->getDomains()[0];
		}

		$url .= '/' . trim($this->getUrl(), '/') . '/' . strtolower($method) . join('/', $parameters);

		return '/' . trim($url, '/') . '/';
	}

	public function renderRoute(Request $request)
	{
		if ($this->getCallback() !== null && is_callable($this->getCallback())) {

			// When the callback is a function
			call_user_func_array($this->getCallback(), $this->getParameters());
		} else {
			// When the callback is a method
			$controller = explode('@', $this->getCallback());
			$className = $this->getNamespace() . '\\' . $controller[0];

			$class = $this->loadClass($className);
			$method = $request->getMethod() . ucfirst($controller[1]);

			if (!method_exists($class, $method)) {
				throw new NotFoundHttpException(sprintf('Method %s does not exist in class %s', $method, $className), 404);
			}

			call_user_func_array([$class, $method], $this->getParameters());

			return $class;
		}

		return null;
	}

	public function matchRoute(Request $request)
	{
		$url = parse_url(urldecode($request->getUri()), PHP_URL_PATH);
		$url = rtrim($url, '/') . '/';

		if (strtolower($url) == strtolower($this->url) || stripos($url, $this->url) === 0) {

			$strippedUrl = trim(str_ireplace($this->url, '/', $url), '/');

			$path = explode('/', $strippedUrl);

			if (count($path) > 0) {

				$method = (!isset($path[0]) || trim($path[0]) === '') ? $this->defaultMethod : $path[0];
				$this->method = $method;

				array_shift($path);
				$this->parameters = $path;

				// Set callback
				$this->setCallback($this->controller . '@' . $this->method);

				return true;
			}
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function getController()
	{
		return $this->controller;
	}

	/**
	 * @param string $controller
	 * @return static
	 */
	public function setController($controller)
	{
		$this->controller = $controller;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * @param string $method
	 * @return static
	 */
	public function setMethod($method)
	{
		$this->method = $method;

		return $this;
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
		if (isset($values['names'])) {
			$this->names = $values['names'];
		}

		parent::setSettings($values, $merge);

		return $this;
	}

}
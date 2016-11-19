<?php
namespace Pecee\SimpleRouter;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

class RouterController extends LoadableRoute implements IControllerRoute
{
	protected $defaultMethod = 'index';
	protected $controller;
	protected $method;

	public function __construct($url, $controller)
	{
		$this->setUrl($url);
		$this->controller = $controller;
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

			call_user_func_array(array($class, $method), $this->getParameters());

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

}
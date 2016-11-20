<?php
namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

interface IRoute
{
	public function renderRoute(Request $request);

	public function loadMiddleware(Request $request, LoadableRoute &$route);

	public function getIdentifier();

	public function setRequestMethods(array $methods);

	public function getRequestMethods();

	public function getParent();

	public function getGroup();

	public function setGroup(RouterGroup $group);

	public function setParent(IRoute $parent);

	public function setCallback($callback);

	public function getCallback();

	public function getMethod();

	public function getClass();

	public function setMethod($method);

	public function setMiddleware($middleware);

	public function setMiddlewares(array $middlewares);

	public function setNamespace($namespace);

	public function setDefaultNamespace($namespace);

	public function getDefaultNamespace();

	public function getMiddlewares();

	public function getNamespace();

	public function getParameters();

	public function setParameters($parameters);

	public function setWhere(array $options);

	public function setMatch($regex);

	public function toArray();

	public function setSettings(array $settings);

	public function matchRoute(Request $request);

}
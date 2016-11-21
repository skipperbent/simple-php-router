<?php
namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;

interface ILoadableRoute extends IRoute
{

	public function findUrl($method = null, $parameters = null, $name = null);

	public function loadMiddleware(Request $request, ILoadableRoute &$route);

	public function getUrl();

	public function setUrl($url);

	public function getName();

	public function hasName($name);

	public function setName($name);

	public function getMiddlewares();

	public function setMiddleware($middleware);

	public function setMiddlewares(array $middlewares);

}
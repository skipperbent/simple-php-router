<?php
namespace Pecee\Http\Middleware;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Route\ILoadableRoute;

interface IMiddleware
{
	/**
	 * @param Request $request
	 * @param ILoadableRoute $route
	 * @return Request|null
	 */
	public function handle(Request $request, ILoadableRoute &$route);

}
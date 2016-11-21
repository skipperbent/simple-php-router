<?php
namespace Demo\Middlewares;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Route\ILoadableRoute;

class ApiVerification implements IMiddleware
{
	public function handle(Request $request, ILoadableRoute &$route)
	{
		// Do authentication
		$request->authenticated = true;

		return $request;
	}

}
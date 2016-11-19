<?php
namespace Pecee\Handler;

use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

interface IExceptionHandler
{
	/**
	 * @param Request $request
	 * @param RouterEntry|null $route
	 * @param \Exception $error
	 * @return Request|null
	 */
	public function handleError(Request $request, RouterEntry &$route = null, \Exception $error);

}
<?php
namespace Pecee\Handler;

use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

interface IExceptionHandler {

	public function handleError(Request $request, RouterEntry $router = null, \Exception $error);

}
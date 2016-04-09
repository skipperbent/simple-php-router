<?php
namespace Pecee\Handler;

use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

abstract class ExceptionHandler {

	abstract public function handleError(Request $request, RouterEntry $router = null, \Exception $error);

}
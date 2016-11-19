<?php

class ExceptionHandler implements \Pecee\Handlers\IExceptionHandler
{
	public function handleError(\Pecee\Http\Request $request, \Pecee\SimpleRouter\RouterEntry &$route = null, \Exception $error)
	{
		throw $error;
	}

}
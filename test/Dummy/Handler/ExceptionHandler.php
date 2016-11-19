<?php

class ExceptionHandler implements \Pecee\Handler\IExceptionHandler
{
	public function handleError(\Pecee\Http\Request $request, \Pecee\SimpleRouter\RouterEntry &$route = null, \Exception $error)
	{
		throw $error;
	}

}
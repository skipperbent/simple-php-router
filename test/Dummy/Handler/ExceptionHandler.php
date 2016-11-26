<?php

class ExceptionHandler implements \Pecee\Handlers\IExceptionHandler
{
	public function handleError(\Pecee\Http\Request $request, \Pecee\SimpleRouter\Route\ILoadableRoute &$route = null, \Exception $error)
	{
	    echo $error->getMessage();
	}

}
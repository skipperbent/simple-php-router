<?php

class TestExceptionHandlerFirst implements \Pecee\Handlers\IExceptionHandler
{
	public function handleError(\Pecee\Http\Request $request, \Pecee\SimpleRouter\Route\ILoadableRoute &$route = null, \Exception $error)
	{
	    echo 'ExceptionHandler 1 loaded' . chr(10);

		$request->setUri('/');
		return $request;
	}

}
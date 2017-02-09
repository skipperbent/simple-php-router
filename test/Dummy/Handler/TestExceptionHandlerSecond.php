<?php

class TestExceptionHandlerSecond implements \Pecee\Handlers\IExceptionHandler
{
	public function handleError(\Pecee\Http\Request $request, \Exception $error)
	{
        echo 'ExceptionHandler 2 loaded' . chr(10);

        $request->setUri('/');
        return $request;
	}

}
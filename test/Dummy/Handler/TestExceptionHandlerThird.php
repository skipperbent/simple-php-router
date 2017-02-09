<?php

class TestExceptionHandlerThird implements \Pecee\Handlers\IExceptionHandler
{
	public function handleError(\Pecee\Http\Request $request, \Exception $error)
	{
        echo 'ExceptionHandler 3 loaded' . chr(10);

		throw new ExceptionHandlerException('All good!', 666);
	}

}
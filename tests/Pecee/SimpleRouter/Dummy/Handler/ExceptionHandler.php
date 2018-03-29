<?php

class ExceptionHandler implements \Pecee\SimpleRouter\Handlers\IExceptionHandler
{
	public function handleError(\Pecee\Http\Request $request, \Exception $error)  : void
	{
	    echo $error->getMessage();
	}

}
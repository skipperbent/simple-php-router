<?php

class ExceptionHandlerFirst implements \Pecee\Handlers\IExceptionHandler
{
	public function handleError(\Pecee\Http\Request $request, \Exception $error)
	{
	    global $stack;
	    $stack[] = static::class;

		$request->setUri(new \Pecee\Http\Uri('/'));
		return $request;
	}

}
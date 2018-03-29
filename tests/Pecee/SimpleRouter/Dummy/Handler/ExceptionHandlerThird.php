<?php

class ExceptionHandlerThird implements \Pecee\SimpleRouter\Handlers\IExceptionHandler
{
	public function handleError(\Pecee\Http\Request $request, \Exception $error) : void
	{
        global $stack;
        $stack[] = static::class;

		throw new ResponseException('ExceptionHandler loaded');
	}

}
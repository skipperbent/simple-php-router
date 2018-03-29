<?php

class ExceptionHandlerSecond implements \Pecee\SimpleRouter\Handlers\IExceptionHandler
{
	public function handleError(\Pecee\Http\Request $request, \Exception $error) : void
	{
        global $stack;
        $stack[] = static::class;

        $request->setUrl(new \Pecee\Http\Url('/'));
	}

}
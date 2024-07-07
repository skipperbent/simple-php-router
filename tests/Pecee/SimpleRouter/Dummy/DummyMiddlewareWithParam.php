<?php

require_once 'Exception/MiddlewareLoadedException.php';

use Pecee\Http\Request;

class DummyMiddlewareWithParam implements \Pecee\Http\Middleware\IMiddleware
{
	public function handle(Request $request, ?string $val1 = null, ?string $val2 = null): void
	{
		throw new MiddlewareLoadedException('Middleware loaded! Param1: ' . $val1 . ' Param2: ' . $val2);
	}

}
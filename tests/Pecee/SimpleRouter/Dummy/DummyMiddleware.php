<?php
require_once 'Exception/MiddlewareLoadedException.php';

use Pecee\Http\Request;

class DummyMiddleware implements \Pecee\Http\Middleware\IMiddleware
{
	public function handle(Request $request) : void
	{
		throw new MiddlewareLoadedException('Middleware loaded!');
	}

}
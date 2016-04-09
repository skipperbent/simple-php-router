<?php

require_once 'Exceptions/MiddlewareLoadedException.php';

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;

class DummyMiddleware implements IMiddleware {

    public function handle(Request $request) {
       throw new MiddlewareLoadedException('Middleware loaded!');
    }

}
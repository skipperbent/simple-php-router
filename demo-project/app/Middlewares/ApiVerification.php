<?php
namespace Demo\Middlewares;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

class ApiVerification implements IMiddleware {

    public function handle(Request $request, RouterEntry &$route) {

        // Do authentication
        $request->authenticated = true;

        return $request;

    }

}
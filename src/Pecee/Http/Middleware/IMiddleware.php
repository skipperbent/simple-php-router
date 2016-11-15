<?php
namespace Pecee\Http\Middleware;

use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

interface IMiddleware {

    /**
     * @param Request $request
     * @param RouterEntry|null $route
     * @return Request|null
     */
    public function handle(Request $request, RouterEntry &$route = null);

}
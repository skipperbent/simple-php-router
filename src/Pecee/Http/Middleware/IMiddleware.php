<?php

namespace Pecee\Http\Middleware;

use Pecee\Http\Request;

interface IMiddleware
{
    /**
     * @param Request $request
     */
    public function handle(Request $request): void;

}
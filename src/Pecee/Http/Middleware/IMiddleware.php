<?php

namespace Pecee\Http\Middleware;

use Pecee\Http\Request;

/**
 * Interface IMiddleware
 *
 * @package Pecee\Http\Middleware
 */
interface IMiddleware
{
    /**
     * @param Request $request
     */
    public function handle(Request $request): void;
}
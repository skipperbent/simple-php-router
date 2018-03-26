<?php

namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

interface IRouterBootManager
{
    /**
     * Called when router loads it's routes
     *
     * @param Request $request
     */
    public function boot(Request $request): void;
}
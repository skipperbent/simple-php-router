<?php

namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

/**
 * Interface IRouterBootManager
 *
 * @package Pecee\SimpleRouter
 */
interface IRouterBootManager
{
    /**
     * @param Router $router
     * @param Request $request
     */
    public function boot(Router $router, Request $request): void;
}
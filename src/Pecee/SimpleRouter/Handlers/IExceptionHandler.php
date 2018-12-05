<?php

namespace Pecee\SimpleRouter\Handlers;

use Pecee\Http\Request;

/**
 * Interface IExceptionHandler
 *
 * @package Pecee\SimpleRouter\Handlers
 */
interface IExceptionHandler
{
    /**
     * @param Request $request
     * @param \Exception $error
     */
    public function handleError(Request $request, \Exception $error): void;
}
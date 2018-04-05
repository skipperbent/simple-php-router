<?php

namespace Pecee\SimpleRouter\Handlers;

use Pecee\Http\Request;

interface IExceptionHandler
{
    /**
     * @param Request $request
     * @param \Exception $error
     */
    public function handleError(Request $request, \Exception $error): void;

}
<?php

namespace Pecee\SimpleRouter\Handlers;

use Pecee\Http\Request;

/**
 * Class CallbackExceptionHandler
 *
 * @package Pecee\SimpleRouter\Handlers
 */
class CallbackExceptionHandler implements IExceptionHandler
{
    /**
     * @var \Closure
     */
    protected $callback;

    /**
     * CallbackExceptionHandler constructor.
     * @param \Closure $callback
     */
    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param Request $request
     * @param \Exception $error
     */
    public function handleError(Request $request, \Exception $error): void
    {
        /* Fire exceptions */
        \call_user_func($this->callback,
            $request,
            $error
        );
    }
}
<?php

namespace Pecee\Http\Input;

use Pecee\Http\Request;

interface IInputValidator
{
    /**
     * @param Request $request
     */
    public function handle(Request $request): bool;

}
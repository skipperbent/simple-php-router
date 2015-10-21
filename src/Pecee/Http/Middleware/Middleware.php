<?php

namespace Pecee\Http\Middleware;

use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

abstract class Middleware
{
    abstract function handle(Request $request);
}
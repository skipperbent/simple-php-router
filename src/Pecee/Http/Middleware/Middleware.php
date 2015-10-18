<?php

namespace Pecee\Http\Middleware;

use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterEntry;

abstract class Middleware
{
    public function handle(Request $request) {
        return true;
    }
}
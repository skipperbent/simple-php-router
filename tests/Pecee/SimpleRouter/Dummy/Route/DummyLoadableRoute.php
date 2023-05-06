<?php

use Pecee\Http\Request;

class DummyLoadableRoute extends Pecee\SimpleRouter\Route\LoadableRoute {

    public function matchRoute(string $url, Request $request): bool
    {
        return false;
    }
}
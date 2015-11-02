<?php
namespace Pecee\Http\Middleware;

use Pecee\Http\Request;

interface IMiddleware {
    public function handle(Request $request);
}
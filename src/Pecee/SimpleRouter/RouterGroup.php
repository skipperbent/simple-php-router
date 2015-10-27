<?php

namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

class RouterGroup extends RouterEntry {

    public function __construct() {
        parent::__construct();
    }

    public function renderRoute(Request $request) {
        // Check if request method is allowed

        if(strtolower($request->getUri()) == strtolower($this->prefix) || stripos($request->getUri(), $this->prefix) === 0) {

            $hasAccess = (!$this->method);

            if($this->method) {
                if(is_array($this->method)) {
                    $hasAccess = (in_array($request->getMethod(), $this->getRequestMethods()));
                } else {
                    $hasAccess = strtolower($this->getRequestMethods()) == strtolower($request->getMethod());
                }
            }

            if(!$hasAccess) {
                throw new RouterException('Method not allowed');
            }

            $this->loadMiddleware($request);

            return parent::renderRoute($request);
        }

        // No match here, move on...
        return null;
    }

    public function matchRoute(Request $request) {
        return null;
    }

}
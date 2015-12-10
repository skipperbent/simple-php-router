<?php

namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

class RouterGroup extends RouterEntry {

    public function __construct() {
        parent::__construct();
    }

    protected function matchDomain() {
        if($this->domain !== null) {

            $parameters = $this->parseParameters($this->domain, request()->getHost(), '[^.]*');

            if($parameters !== null) {
                $this->parameters = $parameters;
            }
        }
    }

    public function renderRoute(Request $request) {
        // Check if request method is allowed
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

        $this->matchDomain();

        return parent::renderRoute($request);
    }

    public function matchRoute(Request $request) {
        return null;
    }

    public function setExceptionHandler($class) {
        $this->exceptionHandler = $class;
        return $this;
    }

    public function getExceptionHandler() {
        return $this->exceptionHandler;
    }

}
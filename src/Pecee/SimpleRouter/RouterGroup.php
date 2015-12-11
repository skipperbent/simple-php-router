<?php

namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

class RouterGroup extends RouterEntry {

    public function __construct() {
        parent::__construct();
    }

    protected function matchDomain(Request $request) {
        if($this->domain !== null) {

            if(is_array($this->domain)) {

                $max = count($this->domain);

                for($i = 0; $i < $max; $i++) {
                    $domain = $this->domain[$i];

                    $parameters = $this->parseParameters($domain, $request->getHost(), '[^.]*');

                    if($parameters !== null) {
                        $this->parameters = $parameters;
                        return true;
                    }
                }

                return null;
            }

            $parameters = $this->parseParameters($this->domain, $request->getHost(), '[^.]*');

            if ($parameters !== null) {
                $this->parameters = $parameters;
                return true;
            }

            return null;
        }

        return false;
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

        if($this->matchDomain($request) === null) {
            return null;
        }

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
<?php

namespace Pecee\SimpleRouter;

use Pecee\Exception\RouterException;
use Pecee\Http\Request;

class RouterGroup extends RouterEntry {

    public function __construct() {
        parent::__construct();
    }

    public function matchDomain(Request $request) {
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

                return false;
            }

            $parameters = $this->parseParameters($this->domain, $request->getHost(), '[^.]*');

            if ($parameters !== null) {
                $this->parameters = $parameters;
                return true;
            }

            return false;
        }

        return true;
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

        $this->matchDomain($request);

        return parent::renderRoute($request);
    }

    public function matchRoute(Request $request) {
        // Skip if prefix doesn't match
        if($this->getPrefix() !== null && stripos($request->getUri(), $this->getPrefix()) === false) {
            return false;
        }

        return $this->matchDomain($request);
    }

    public function setExceptionHandler($class) {
        $this->exceptionHandler = $class;
        return $this;
    }

    public function getExceptionHandler() {
        return $this->exceptionHandler;
    }

    public function getDomain() {
        return $this->domain;
    }

}
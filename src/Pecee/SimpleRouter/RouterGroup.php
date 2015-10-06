<?php

namespace Pecee\SimpleRouter;

class RouterGroup extends RouterEntry {

    public function __construct() {
        parent::__construct();
    }

    public function matchRoute($requestMethod, $url) {
        // Check if request method is allowed

        if(count($this->method) === 0 || strtolower($this->method) == strtolower($requestMethod) || is_array($this->method) && in_array($this->method, self::$allowedRequestTypes)) {
            return $this;
        }

        // No match here, move on...
        return null;
    }

}
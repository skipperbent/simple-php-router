<?php

namespace Pecee\SimpleRouter;

class RouterGroup extends RouterEntry {

    public function __construct() {
        parent::__construct();
    }

    public function matchRoute($requestMethod, $url) {
        // Check if request method is allowed

        if(strtolower($url) == strtolower($this->prefix) || stripos($url, $this->prefix) === 0) {

            $hasAccess = (!$this->method);

            if($this->method) {
                if(is_array($this->method)) {
                    $hasAccess = (in_array($requestMethod, $this->method));
                } else {
                    $hasAccess = strtolower($this->method) == strtolower($requestMethod);
                }
            }

            if(!$hasAccess) {
                throw new RouterException('Method not allowed');
            }

            return $this;
        }

        // No match here, move on...
        return null;
    }

}
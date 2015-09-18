<?php

namespace Pecee\SimpleRouter;

class RouterGroup extends RouterEntry {

    public function __construct() {
        parent::__construct();
    }

    public function getRoute($requestMethod, &$url) {
        // Check if request method is allowed
        if(count($this->requestTypes) === 0 || in_array($requestMethod, $this->requestTypes)) {
            return $this;
        }

        // No match here, move on...
        return null;
    }

}
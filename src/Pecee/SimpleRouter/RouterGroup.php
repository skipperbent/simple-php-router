<?php

namespace Pecee\SimpleRouter;

class RouterGroup extends RouterEntry {

    protected $requestTypes;

    public function __construct() {
        parent::__construct();
        $this->requestTypes = array();
    }

    public function getRoute($requestMethod, &$url) {
        // Check if request method is allowed
        if(count($this->requestTypes) === 0 || in_array($requestMethod, $this->requestTypes)) {
            return $this;
        }

        // No match here, move on...
        return null;
    }

    /**
     * Add request type
     *
     * @param $type
     * @return self
     * @throws RouterException
     */
    public function addRequestType($type) {
        if(!in_array($type, self::$allowedRequestTypes)) {
            throw new RouterException('Invalid request method: ' . $type);
        }

        $this->requestTypes[] = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequestTypes() {
        return $this->requestTypes;
    }

}
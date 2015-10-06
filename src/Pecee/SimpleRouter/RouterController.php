<?php
namespace Pecee\SimpleRouter;

class RouterController extends RouterEntry {

    const DEFAULT_METHOD = 'index';

    protected $url;
    protected $controller;
    protected $method;

    public function __construct($url, $controller) {
        parent::__construct();
        $this->url = $url;
        $this->controller = $controller;
    }

    public function matchRoute($requestMethod, $url) {

        $url = parse_url($url);
        $url = $url['path'];

        if(strtolower($url) == strtolower($this->url) || stripos($url, $this->url) === 0) {

            $strippedUrl = trim(str_ireplace($this->url, '/', $url), '/');

            $path = explode('/', $strippedUrl);

            if(count($path)) {

                $method = (!isset($path[0]) || trim($path[0]) === '') ? self::DEFAULT_METHOD : $path[0];
                $this->method = $method;

                array_shift($path);
                $this->parameters = $path;

                // Set callback
                $this->setCallback($this->controller . '@' . $this->method);

                return $this;
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url) {
        $url = rtrim($url, '/') . '/';
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getController() {
        return $this->controller;
    }

    /**
     * @param string $controller
     */
    public function setController($controller) {
        $this->controller = $controller;
    }

    /**
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method) {
        $this->method = $method;
    }

}
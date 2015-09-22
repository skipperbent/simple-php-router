<?php
namespace Pecee\SimpleRouter;

class RouterController extends RouterEntry {

    const DEFAULT_METHOD = 'index';

    protected $url;
    protected $controller;
    protected $method;
    protected $parameters;

    public function __construct($url, $controller) {
        parent::__construct();
        $this->url = $url;
        $this->controller = $controller;
        $this->parameters;
    }

    protected function loadClass() {

        if($this->getNamespace()) {
            $className = $this->getNamespace() . '\\' . $this->controller;
        } else {
            $className = $this->controller;
        }

        if(!class_exists($className)) {
            throw new RouterException(sprintf('Controller %s not found', $className), 404);
        }

        // Call controller
        $class = new $className();

        if(!method_exists($class, $this->method)) {
            throw new RouterException(sprintf('Method %s not found in controller %s', $this->method, $className), 404);
        }

        call_user_func_array(array($class, $this->method), $this->parameters);
    }

    public function getRoute($requestMethod, &$url) {

        $url = parse_url($url);
        $url = $url['path'];

        if(strtolower($url) == strtolower($this->url) || stripos($url, $this->url) !== false) {

            $strippedUrl = trim(str_ireplace($this->url, '/', $url), '/');

            $path = explode('/', $strippedUrl);

            if(count($path)) {

                $method = (!isset($path[0]) || trim($path[0]) === '') ? self::DEFAULT_METHOD : $path[0];

                $this->method = $requestMethod . ucfirst($method);

                array_shift($path);

                $this->parameters = $path;

                $this->loadClass();
            }

        }

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
        $this->url = $url;
    }

    /**
     * @return array
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     */
    public function setParameters($parameters) {
        $this->parameters = $parameters;
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
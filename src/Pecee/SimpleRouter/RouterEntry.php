<?php

namespace Pecee\SimpleRouter;

abstract class RouterEntry {

    const REQUEST_TYPE_POST = 'post';
    const REQUEST_TYPE_GET = 'get';
    const REQUEST_TYPE_PUT = 'put';
    const REQUEST_TYPE_DELETE = 'delete';

    public static $allowedRequestTypes = array(
        self::REQUEST_TYPE_DELETE,
        self::REQUEST_TYPE_GET,
        self::REQUEST_TYPE_POST,
        self::REQUEST_TYPE_PUT
    );

    protected $settings;
    protected $requestTypes;
    protected $callback;
    protected $parameters;

    public function __construct() {
        $this->settings = array();
        $this->requestTypes = array();
        $this->parameters = array();
    }

    protected function parseParameter($path) {
        $parameters = array();

        preg_match('/{([A-Za-z\-\_]*?)}/is', $path, $parameters);

        if(isset($parameters[1]) && count($parameters[1]) > 0) {
            return $parameters[1];
        }

        return null;
    }

    /**
     * @param string $callback
     * @return self;
     */
    public function setCallback($callback) {
        $this->callback = $callback;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCallback() {
        return $this->callback;
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

    /**
     * @return mixed
     */
    public function getParameters(){
        return $this->parameters;
    }

    /**
     * @param mixed $parameters
     * @return self
     */
    public function setParameters($parameters) {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @param string $prefix
     * @return self
     */
    public function setPrefix($prefix) {
        $this->prefix = trim($prefix, '/');
        return $this;
    }

    /**
     * @param string $middleware
     * @return self
     */
    public function setMiddleware($middleware) {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * @param string $namespace
     * @return self
     */
    public function setNamespace($namespace) {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix() {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getMiddleware() {
        return $this->middleware;
    }

    /**
     * @return string
     */
    public function getNamespace() {
        return $this->namespace;
    }

    /**
     * @return array
     */
    public function getSettings() {
        return $this->settings;
    }

    /**
     * Get settings that are allowed to be inherited by child routes.
     *
     * @return array
     */
    public function getMergeableSettings() {
        $settings = $this->settings;

        if(isset($settings['middleware'])) {
            unset($settings['middleware']);
        }

        if(isset($settings['prefix'])) {
            unset($settings['prefix']);
        }

        return $settings;
    }

    /**
     * @param array $settings
     * @return self
     */
    public function setSettings($settings) {
        $this->settings = $settings;
        return $this;
    }

    /**
     * Dynamicially access settings value
     *
     * @param $name
     * @return mixed|null
     */
    public function __get($name) {
        return (isset($this->settings[$name]) ? $this->settings[$name] : null);
    }

    /**
     * Dynamicially set settings value
     *
     * @param string $name
     * @param mixed|null $value
     */
    public function __set($name, $value = null) {
        $this->settings[$name] = $value;
    }

    abstract function getRoute($requestMethod, &$url);

}
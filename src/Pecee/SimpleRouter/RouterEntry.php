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
    protected $callback;
    protected $parameters;

    public function __construct() {
        $this->settings = array();
        $this->parameters = array();
    }

    protected function loadClass($name) {
        if(!class_exists($name)) {
            throw new RouterException(sprintf('Class %s does not exist', $name));
        }

        return new $name();
    }

    /**
     * Returns callback name/identifier for the current route based on the callback.
     * Useful if you need to get a unique identifier for the loaded route, for instance
     * when using translations etc.
     *
     * @return string
     */
    public function getIdentifier() {
        if(strpos($this->callback, '@') !== false) {
            return $this->callback;
        }
        return 'function_' . md5($this->callback);
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
        $this->prefix = '/' . trim($prefix, '/') . '/';
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
    public function addSettings(array $settings) {
        array_merge($this->settings, $settings);
        return $this;
    }

    /**
     * @param array $settings
     * @return self
     */
    public function setSettings($settings) {
        $this->settings = $settings;

        if($settings['prefix']) {
            $this->setPrefix($settings['prefix']);
        }

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

    public function renderRoute($requestMethod) {
        // Load middleware
        if($this->getMiddleware()) {
            $this->loadClass($this->getMiddleware());
        }

        if(is_object($this->getCallback()) && is_callable($this->getCallback())) {

            // When the callback is a function
            call_user_func_array($this->getCallback(), $this->getParameters());
        } else {
            // When the callback is a method
            $controller = explode('@', $this->getCallback());
            $className = $this->getNamespace() . '\\' . $controller[0];
            $class = $this->loadClass($className);
            $method = $requestMethod . ucfirst($controller[1]);

            if (!method_exists($class, $method)) {
                throw new RouterException(sprintf('Method %s does not exist in class %s', $method, $className), 404);
            }

            call_user_func_array(array($class, $method), $this->getParameters());

            return $class;
        }

        return null;
    }

    abstract function matchRoute($requestMethod, $url);

}
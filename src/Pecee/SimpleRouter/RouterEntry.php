<?php

namespace Pecee\SimpleRouter;

use Pecee\Http\Middleware\Middleware;
use Pecee\Http\Request;

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
    protected $parametersRegex;
    protected $regexMatch;

    public function __construct() {
        $this->settings = array();
        $this->settings['requestMethods'] = array();
        $this->parameters = array();
        $this->parametersRegex = array();
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

    public function getMethod() {
        if(strpos($this->callback, '@') !== false) {
            $tmp = explode('@', $this->callback);
            return $tmp[1];
        }
        return null;
    }

    public function getClass() {
        if(strpos($this->callback, '@') !== false) {
            $tmp = explode('@', $this->callback);
            return $tmp[0];
        }
        return null;
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
     * Add regular expression parameter match
     *
     * @param array $options
     * @return self
     */
    public function where(array $options) {
        $this->parametersRegex = array_merge($this->parametersRegex, $options);
        return $this;
    }

    /**
     * Add regular expression match for url
     *
     * @param string $regex
     * @return self
     */
    public function match($regex) {
        $this->regexMatch = $regex;
        return $this;
    }

    /**
     * Get settings that are allowed to be inherited by child routes.
     *
     * @return array
     */
    public function getMergeableSettings() {
        $settings = $this->settings;

        /*if(isset($settings['middleware'])) {
            unset($settings['middleware']);
        }*/

        if(isset($settings['prefix'])) {
            unset($settings['prefix']);
        }

        return $settings;
    }

    /**
     * @param array $settings
     * @return self
     */
    public function addSettings(array $settings = null) {
        if(is_array($settings)) {
            $this->settings = array_merge($this->settings, $settings);
        }
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

    protected function loadClass($name) {
        if(!class_exists($name)) {
            throw new RouterException(sprintf('Class %s does not exist', $name));
        }

        return new $name();
    }

    public function loadMiddleware(Request $request) {
        if($this->getMiddleware()) {
            $middleware = $this->loadClass($this->getMiddleware());
            if (!($middleware instanceof Middleware)) {
                throw new RouterException($this->getMiddleware() . ' must be instance of Middleware');
            }

            /* @var $class Middleware */
            $middleware->handle($request);
        }
    }

    public function renderRoute(Request $request) {

        if(is_object($this->getCallback()) && is_callable($this->getCallback())) {

            // When the callback is a function
            call_user_func_array($this->getCallback(), $this->getParameters());
        } else {
            // When the callback is a method
            $controller = explode('@', $this->getCallback());
            $className = $this->getNamespace() . '\\' . $controller[0];

            $class = $this->loadClass($className);
            $method = $controller[1];

            if (!method_exists($class, $method)) {
                throw new RouterException(sprintf('Method %s does not exist in class %s', $method, $className), 404);
            }

            call_user_func_array(array($class, $method), $this->getParameters());

            return $class;
        }

        return null;
    }

    /**
     * Set allowed request methods
     *
     * @param array $methods
     * @return self $this
     */
    public function setRequestMethods(array $methods) {
        $this->settings['requestMethods'] = $methods;
        return $this;
    }

    /**
     * Get allowed requeset methods
     *
     * @return array
     */
    public function getRequestMethods() {
        if(!isset($this->settings['requestMethods']) || isset($this->settings['requestMethods']) && !is_array($this->settings['requestMethods'])) {
            $value = isset($this->settings['requestMethods']) ? $this->settings['requestMethods'] : null;
            return array($value);
        }
        return $this->settings['requestMethods'];
    }

    abstract function matchRoute(Request $request);

}
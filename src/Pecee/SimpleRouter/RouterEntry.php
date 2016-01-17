<?php

namespace Pecee\SimpleRouter;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;

abstract class RouterEntry {

    const REQUEST_TYPE_POST = 'post';
    const REQUEST_TYPE_GET = 'get';
    const REQUEST_TYPE_PUT = 'put';
    const REQUEST_TYPE_PATCH = 'patch';
    const REQUEST_TYPE_DELETE = 'delete';

    public static $allowedRequestTypes = array(
        self::REQUEST_TYPE_DELETE,
        self::REQUEST_TYPE_GET,
        self::REQUEST_TYPE_POST,
        self::REQUEST_TYPE_PUT,
        self::REQUEST_TYPE_PATCH
    );

    protected $settings;
    protected $callback;

    public function __construct() {
        $this->settings = array();
        $this->settings['requestMethods'] = array();
        $this->settings['where'] = array();
        $this->settings['parameters'] = array();
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

    public function setMethod($method) {
        $this->callback = sprintf('%s@%s', $this->getClass(), $method);
        return $this;
    }

    public function setClass($class) {
        $this->callback = sprintf('%s@%s', $class, $this->getMethod());
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
     * @return mixed
     */
    public function getParameters(){
        return ($this->parameters === null) ? array() : $this->parameters;
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
        $this->where = array_merge($this->where, $options);
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

        if(isset($settings['prefix'])) {
            $this->setPrefix($settings['prefix']);
        }

        return $this;
    }

    /**
     * Dynamically access settings value
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

    protected function parseParameters($route, $url, $parameterRegex = '[a-z0-9]+?') {
        $parameterNames = array();
        $regex = '';
        $lastCharacter = '';
        $isParameter = false;
        $parameter = '';

        $routeLength = strlen($route);
        for($i = 0; $i < $routeLength; $i++) {

            $character = $route[$i];

            // Skip "/" if we are at the end of a parameter
            if($lastCharacter === '}' && $character === '/') {
                $lastCharacter = $character;
                continue;
            }

            if($character === '{') {
                // Remove "/" and "\" from regex
                if(substr($regex, strlen($regex)-1) === '/') {
                    $regex = substr($regex, 0, strlen($regex) - 2);
                }

                $isParameter = true;
            } elseif($isParameter && $character === '}') {
                $required = true;
                // Check for optional parameter

                // Use custom parameter regex if it exists
                if(is_array($this->where) && isset($this->where[$parameter])) {
                    $parameterRegex = $this->where[$parameter];
                }

                if($lastCharacter === '?') {
                    $parameter = substr($parameter, 0, strlen($parameter)-1);
                    $regex .= '(?:\\/?(?P<'.$parameter.'>[^\/]+)?\\/?)';
                    $required = false;
                } else {
                    $regex .= '\\/(?P<' . $parameter . '>'. $parameterRegex .')\\/';
                }
                $parameterNames[] = array('name' => $parameter, 'required' => $required);
                $parameter = '';
                $isParameter = false;

            } elseif($isParameter) {
                $parameter .= $character;
            } elseif($character === '/') {
                $regex .= '\\' . $character;
            } else {
                $regex .= str_replace('.', '\\.', $character);
            }

            $lastCharacter = $character;
        }

        $parameterValues = array();

        if(preg_match('/^'.$regex.'$/is', $url, $parameterValues)) {
            $parameters = array();

            $max = count($parameterNames);

            if($max) {
                for($i = 0; $i < $max; $i++) {
                    $name = $parameterNames[$i];
                    $parameterValue = (isset($parameterValues[$name['name']]) && !empty($parameterValues[$name['name']])) ? $parameterValues[$name['name']] : null;

                    if($name['required'] && $parameterValue === null) {
                        throw new RouterException('Missing required parameter ' . $name['name'], 404);
                    }

                    if(!$name['required'] && $parameterValue === null) {
                        continue;
                    }

                    $parameters[$name['name']] = $parameterValue;
                }
            }

            return $parameters;
        }

        return null;
    }

    public function loadMiddleware(Request $request) {
        if($this->getMiddleware()) {
            if(is_array($this->getMiddleware())) {
                foreach($this->getMiddleware() as $middleware) {
                    $middleware = $this->loadClass($middleware);
                    if (!($middleware instanceof IMiddleware)) {
                        throw new RouterException($middleware . ' must be instance of Middleware');
                    }

                    /* @var $class IMiddleware */
                    $middleware->handle($request);
                }
            } else {
                $middleware = $this->loadClass($this->getMiddleware());
                if (!($middleware instanceof IMiddleware)) {
                    throw new RouterException($this->getMiddleware() . ' must be instance of Middleware');
                }

                /* @var $class IMiddleware */
                $middleware->handle($request);
            }
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

            $parameters = array_filter($this->getParameters(), function($var){
                return !is_null($var);
            });

            call_user_func_array(array($class, $method), $parameters);

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

    public function getGroup() {
        return $this->group;
    }

    public function setGroup($group) {
        $this->group = $group;
        return $this;
    }

    abstract function matchRoute(Request $request);

}
<?php

namespace Pecee\SimpleRouter;

use Pecee\Exception\RouterException;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;

abstract class RouterEntry {

    const REQUEST_TYPE_POST = 'post';
    const REQUEST_TYPE_GET = 'get';
    const REQUEST_TYPE_PUT = 'put';
    const REQUEST_TYPE_PATCH = 'patch';
    const REQUEST_TYPE_DELETE = 'delete';

    public static $allowedRequestTypes = [
        self::REQUEST_TYPE_DELETE,
        self::REQUEST_TYPE_GET,
        self::REQUEST_TYPE_POST,
        self::REQUEST_TYPE_PUT,
        self::REQUEST_TYPE_PATCH,
    ];

    protected $settings = [
        'requestMethods' => array(),
        'where' => array(),
        'parameters' => array(),
        'middleware' => array(),
    ];

    protected $callback;

    /**
     * @param string $callback
     * @return static
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
     * @param string $middleware
     * @return static
     */
    public function setMiddleware($middleware) {
        $this->settings['middleware'][] = $middleware;
        return $this;
    }

    /**
     * @param string $namespace
     * @return static
     */
    public function setNamespace($namespace) {
        $this->settings['namespace'] = $namespace;
        return $this;
    }

    /**
     * @return string|array
     */
    public function getMiddleware() {
        return $this->settingArray('middleware');
    }

    /**
     * @return string
     */
    public function getNamespace() {
        return $this->setting('namespace');
    }

    /**
     * @return array
     */
    public function getSettings() {
        return $this->settings;
    }

    /**
     * @return array
     */
    public function getParameters(){
        return $this->setting('parameters', array());
    }

    /**
     * @param mixed $parameters
     * @return static
     */
    public function setParameters($parameters) {
        $this->settings['parameters'] = $parameters;
        return $this;
    }

    /**
     * Add regular expression parameter match
     *
     * @param array $options
     * @return static
     */
    public function where(array $options) {
        $this->settings['where'] = array_merge($this->settings['where'], $options);
        return $this;
    }

    /**
     * Add regular expression match for url
     *
     * @param string $regex
     * @return static
     */
    public function match($regex) {
        $this->settings['regexMatch'] = $regex;
        return $this;
    }

    /**
     * Get settings that are allowed to be inherited by child routes.
     *
     * @return array
     */
    public function getMergeableSettings() {
        return $this->settings;
    }

    /**
     * @param array $settings
     * @return static
     */
    public function addSettings(array $settings) {
        $this->settings = array_merge($this->settings, $settings);
        return $this;
    }

    /**
     * @param array $settings
     * @return static
     */
    public function setSettings($settings) {
        $this->settings = $settings;
        return $this;
    }

    protected function loadClass($name) {
        if(!class_exists($name)) {
            throw new RouterException(sprintf('Class %s does not exist', $name));
        }

        return new $name();
    }

    protected function parseParameters($route, $url, $parameterRegex = '[\w]+') {
        $parameterNames = array();
        $regex = '';
        $lastCharacter = '';
        $isParameter = false;
        $parameter = '';

        $routeLength = strlen($route);
        for($i = 0; $i < $routeLength; $i++) {

            $character = $route[$i];

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
                if(is_array($this->setting('where')) && isset($this->settings['where'][$parameter])) {
                    $parameterRegex = $this->settings['where'][$parameter];
                }

                if($lastCharacter === '?') {
                    $parameter = substr($parameter, 0, strlen($parameter)-1);
                    $regex .= '(?:\/?(?P<' . $parameter . '>'. $parameterRegex .')[^\/]?)?';
                    $required = false;
                } else {
                    $regex .= '\/?(?P<' . $parameter . '>'. $parameterRegex .')[^\/]?';
                }

                $parameterNames[] = [
                    'name' => $parameter,
                    'required' => $required
                ];

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

        if(preg_match('/^'.$regex.'\/?$/is', $url, $parameterValues)) {

            $parameters = array();

            $max = count($parameterNames);

            for($i = 0; $i < $max; $i++) {
                $name = $parameterNames[$i];
                $parameterValue = isset($parameterValues[$name['name']]) ? $parameterValues[$name['name']] : null;

                if($name['required'] && $parameterValue === null) {
                    throw new RouterException('Missing required parameter ' . $name['name'], 404);
                }

                if(!$name['required'] && $parameterValue === null) {
                    continue;
                }

                $parameters[$name['name']] = $parameterValue;
            }

            return $parameters;
        }

        return null;
    }

    public function loadMiddleware(Request $request, RouterEntry &$route) {
        if(count($this->getMiddleware())) {
            foreach($this->getMiddleware() as $middleware) {
                $middleware = $this->loadClass($middleware);
                if (!($middleware instanceof IMiddleware)) {
                    throw new RouterException($middleware . ' must be instance of Middleware');
                }

                /* @var $class IMiddleware */
                $middleware->handle($request, $route);
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
                return ($var !== null);
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
     * @return static $this
     */
    public function setRequestMethods(array $methods) {
        $this->settings['requestMethods'] = $methods;
        return $this;
    }

    /**
     * Get allowed request methods
     *
     * @return array
     */
    public function getRequestMethods() {
        return $this->settingArray('requestMethods');
    }

    public function getGroup() {
        return $this->setting('group');
    }

    public function setGroup($group) {
        $this->settings['group'] = $group;
        return $this;
    }

    protected function setting($name, $defaultValue = null) {
        return isset($this->settings[$name]) ? $this->settings[$name] : $defaultValue;
    }

    protected function settingArray($name) {
        $value = $this->setting($name);

        if($value === null) {
            return [];
        }

        return (!is_array($value)) ? array($value) : $value;
    }

    abstract function matchRoute(Request $request);

}
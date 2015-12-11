<?php
namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

class RouterResource extends RouterEntry {

    const DEFAULT_METHOD = 'index';

    protected $url;
    protected $controller;
    protected $method;
    protected $postMethod;

    public function __construct($url, $controller) {
        parent::__construct();
        $this->url = $url;
        $this->controller = $controller;
        $this->postMethod = strtolower(($_SERVER['REQUEST_METHOD'] != 'GET') ? 'post' : 'get');
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
            $method = strtolower($controller[1]);

            if (!method_exists($class, $method)) {
                throw new RouterException(sprintf('Method %s does not exist in class %s', $method, $className), 404);
            }

            call_user_func_array(array($class, $method), $this->getParameters());

            return $class;
        }

        return null;
    }

    protected function call($method, $parameters) {
        $this->setCallback($this->controller . '@' . $method);
        $this->parameters = $parameters;
        return true;
    }

    public function matchRoute(Request $request) {
        $url = parse_url($request->getUri());
        $url = rtrim($url['path'], '/') . '/';

        if(strtolower($url) == strtolower($this->url) || stripos($url, $this->url) === 0) {
            $url = rtrim($url, '/');

            $strippedUrl = trim(substr($url, strlen($this->url)), '/');
            $path = explode('/', $strippedUrl);

            $args = $path;
            $action = '';

            if(count($args)) {
                $action = $args[0];
                array_shift($args);
            }

            if (count($path)) {

                // Delete
                if($request->getMethod() === self::REQUEST_TYPE_DELETE && $this->postMethod === self::REQUEST_TYPE_POST) {
                    return $this->call('destroy', $args);
                }

                // Update
                if(in_array($request->getMethod(), array('put', 'patch')) && $this->postMethod === self::REQUEST_TYPE_POST) {
                    return $this->call('update', array_merge(array($action), $args));
                }

                // Edit
                if(isset($args[0]) && strtolower($args[0]) === 'edit' && $this->postMethod === self::REQUEST_TYPE_GET) {
                    return $this->call('edit', array_merge(array($action), array_slice($args, 1)));
                }

                // Create
                if(strtolower($action) === 'create' && $request->getMethod() === self::REQUEST_TYPE_GET) {
                    return $this->call('create', $args);
                }

                // Save
                if($this->postMethod === self::REQUEST_TYPE_POST) {
                    return $this->call('store', $args);
                }

                // Show
                if($action && $this->postMethod === self::REQUEST_TYPE_GET) {
                    return $this->call('show', array_merge(array($action), $args));
                }

                // Index
                return $this->call('index', $args);
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
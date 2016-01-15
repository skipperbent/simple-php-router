<?php
namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

class RouterResource extends RouterEntry {

    protected $url;
    protected $controller;
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

        $route = rtrim($this->url, '/') . '/{id?}/{action?}';

        $parameters = $this->parseParameters($route, $url, '[0-9]+?');

        if($parameters !== null) {

            if(is_array($parameters)) {
                $parameters = array_merge($this->parameters, $parameters);
            }

            $action = isset($parameters['action']) ? $parameters['action'] : null;
            unset($parameters['action']);

            // Delete
            if($request->getMethod() === self::REQUEST_TYPE_DELETE && $this->postMethod === self::REQUEST_TYPE_POST) {
                return $this->call('destroy', $parameters);
            }

            // Update
            if(in_array($request->getMethod(), array(self::REQUEST_TYPE_PATCH, self::REQUEST_TYPE_PUT)) && $this->postMethod === self::REQUEST_TYPE_POST) {
                return $this->call('update', $parameters);
            }

            // Edit
            if(isset($action) && strtolower($action) === 'edit' && $this->postMethod === self::REQUEST_TYPE_GET) {
                return $this->call('edit', $parameters);
            }

            // Create
            if(strtolower($action) === 'create' && $request->getMethod() === self::REQUEST_TYPE_GET) {
                return $this->call('create', $parameters);
            }

            // Save
            if($this->postMethod === self::REQUEST_TYPE_POST) {
                return $this->call('store', $parameters);
            }

            // Show
            if(isset($parameters['id']) && $this->postMethod === self::REQUEST_TYPE_GET) {
                return $this->call('show', $parameters);
            }

            // Index
            return $this->call('index', $parameters);
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

}
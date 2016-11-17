<?php
namespace Pecee\SimpleRouter;

use Pecee\Exception\RouterException;
use Pecee\Http\Request;

class RouterResource extends LoadableRoute implements IControllerRoute {

    protected $controller;

    public function __construct($url, $controller) {
        $this->url = $url;
        $this->controller = $controller;
    }

    public function renderRoute(Request $request) {
        if($this->getCallback() !== null && is_callable($this->getCallback())) {
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
        $url = parse_url(urldecode($request->getUri()), PHP_URL_PATH);
        $url = rtrim($url, '/') . '/';

        $route = rtrim($this->url, '/') . '/{id?}/{action?}';

        $parameters = $this->parseParameters($route, $url);

        if($parameters !== null) {

            if(is_array($parameters)) {
                $parameters = array_merge($this->parameters, $parameters);
            }

            $action = isset($parameters['action']) ? $parameters['action'] : null;
            unset($parameters['action']);

            // Delete
            if($request->getMethod() === static::REQUEST_TYPE_DELETE && $request->getMethod() === static::REQUEST_TYPE_POST) {
                return $this->call('destroy', $parameters);
            }

            // Update
            if(in_array($request->getMethod(), array(static::REQUEST_TYPE_PATCH, static::REQUEST_TYPE_PUT)) && $request->getMethod() === static::REQUEST_TYPE_POST) {
                return $this->call('update', $parameters);
            }

            // Edit
            if(isset($action) && strtolower($action) === 'edit' && $request->getMethod() === static::REQUEST_TYPE_GET) {
                return $this->call('edit', $parameters);
            }

            // Create
            if(strtolower($action) === 'create' && $request->getMethod() === static::REQUEST_TYPE_GET) {
                return $this->call('create', $parameters);
            }

            // Save
            if($request->getMethod() === static::REQUEST_TYPE_POST) {
                return $this->call('store', $parameters);
            }

            // Show
            if(isset($parameters['id']) && $request->getMethod() === static::REQUEST_TYPE_GET) {
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
    public function getController() {
        return $this->controller;
    }

    /**
     * @param string $controller
     * @return static
     */
    public function setController($controller) {
        $this->controller = $controller;
        return $this;
    }

}
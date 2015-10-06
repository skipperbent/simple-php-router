<?php
namespace Pecee\SimpleRouter;

class RouterRessource extends RouterEntry {

    const DEFAULT_METHOD = 'index';

    protected $url;
    protected $controller;
    protected $method;
    protected $parameters;
    protected $postMethod;

    public function __construct($url, $controller) {
        parent::__construct();
        $this->url = $url;
        $this->controller = $controller;
        $this->parameters;
        $this->postMethod = strtoupper(isset($_POST['_method']) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD']);
    }

    public function renderRoute($requestMethod) {
        return parent::renderRoute($requestMethod);
    }

    protected function call($method, $parameters) {
        $this->setCallback($this->controller . '@' . $method);
        $this->parameters = $parameters;
        return $this;
    }

    public function matchRoute($requestMethod, $url) {
        $url = parse_url($url);
        $url = $url['path'];

        if(strtolower($url) == strtolower($this->url) || stripos($url, $this->url) !== false) {
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
                if($this->postMethod == 'DELETE' && $requestMethod == self::REQUEST_TYPE_POST) {
                    return $this->call('destroy', $args);
                }

                // Update
                if(in_array($this->postMethod, array('PUT', 'PATCH')) && $requestMethod == self::REQUEST_TYPE_POST) {
                    return $this->call('update', array_merge(array($action), $args));
                }

                // Edit
                if(isset($args[0]) && strtolower($args[0]) == 'edit' && $requestMethod == self::REQUEST_TYPE_GET) {
                    return $this->call('edit', array_merge(array($action), array_slice($args, 1)));
                }

                // Create
                if(strtolower($action) == 'create' && $this->postMethod == 'GET') {
                    return $this->call('create', $args);
                }

                // Save
                if($requestMethod == 'POST') {
                    return $this->call('store', $args);
                }

                // Show
                if($action && $requestMethod == 'GET') {
                    return $this->call('show', array_merge(array($action), $args));
                }

                // Index
                return $this->call('index', $args);

            }
        }

        return null;
    }

}
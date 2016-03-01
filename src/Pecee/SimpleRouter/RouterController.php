<?php
namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

class RouterController extends RouterEntry {

    const DEFAULT_METHOD = 'index';

    protected $url;
    protected $controller;
    protected $method;

    public function __construct($url, $controller) {
        parent::__construct();
        $this->url = $url;
        $this->controller = $controller;
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
            $method = $request->getMethod() . ucfirst($controller[1]);

            if (!method_exists($class, $method)) {
                throw new RouterException(sprintf('Method %s does not exist in class %s', $method, $className), 404);
            }

            call_user_func_array(array($class, $method), $this->getParameters());

            return $class;
        }

        return null;
    }

    public function matchRoute(Request $request) {
        $url = parse_url(urldecode($request->getUri()));
        $url = rtrim($url['path'], '/') . '/';

        if(strtolower($url) == strtolower($this->url) || stripos($url, $this->url) === 0) {

            $strippedUrl = trim(str_ireplace($this->url, '/', $url), '/');

            $path = explode('/', $strippedUrl);

            if(count($path)) {

                $method = (!isset($path[0]) || trim($path[0]) === '') ? self::DEFAULT_METHOD : $path[0];
                $this->method = $method;

                array_shift($path);
                $this->parameters = $path;

                // Set callback
                $this->setCallback($this->controller . '@' . $this->method);

                return true;
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
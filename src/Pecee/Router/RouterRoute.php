<?php

namespace Pecee\Router;

use Pecee\Registry;
use Pecee\SimpleRouter;

class RouterRoute extends RouterEntry {

    protected $url;

    public function __construct($url, $callback) {
        parent::__construct();
        $this->url = $url;
        $this->callback = $callback;

        $this->settings['aliases'] = array();

        // Set default namespace
        $this->namespace = Registry::GetInstance()->get(SimpleRouter::SETTINGS_APPNAME, false) . '\\' . 'Controller';
    }

    public function getRoute($requestMethod, &$url) {

        // Check if request method is allowed
        if(count($this->requestTypes) === 0 || in_array($requestMethod, $this->requestTypes)) {

            $url = explode('/', trim($url, '/'));
            $route = explode('/', trim($this->url, '/'));

            // Check if url parameter count matches
            if(count($url) === count($route)) {

                $parameters = array();

                $matches = true;

                // Check if url matches
                foreach($route as $i => $path) {
                    $parameter = $this->parseParameter($path);

                    // Check if parameter of path matches, otherwise quit..
                    if(is_null($parameter) && strtolower($path) != strtolower($url[$i])) {
                        $matches = false;
                        break;
                    }

                    // Save parameter if we have one
                    if($parameter) {
                        $parameters[$parameter] = $url[$i];
                    }
                }

                // This route matches
                if($matches) {
                    $this->parameters = $parameters;
                    return $this;
                }

            }
        }

        // No match here, move on...
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
     * @return self
     */
    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    /**
     * @param array $aliases
     * @return self
     */
    public function setAliases(array $aliases) {
        $this->aliases = $aliases;
        return $this;
    }

    /**
     * Add alias
     *
     * @param $alias
     * @return self
     */
    public function addAlias($alias) {
        $this->aliases[] = $alias;
        return $this;
    }

    public function getAliases() {
        $this->aliases;
    }
}
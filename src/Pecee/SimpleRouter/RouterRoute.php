<?php

namespace Pecee\SimpleRouter;

use Pecee\Registry;
use Pecee\Router;

class RouterRoute extends RouterEntry {

    protected $url;
    protected $requestTypes;

    public function __construct($url, $callback) {
        parent::__construct();
        $this->setUrl($url);
        $this->setCallback($callback);

        $this->settings['aliases'] = array();
        $this->requestTypes = array();
    }

    protected function parseParameters($url) {
        $parameters = array();

        preg_match_all('/{([A-Za-z\-\_]*?)}/is', $url, $parameters);

        if(isset($parameters[1]) && count($parameters[1]) > 0) {
            return $parameters[1];
        }

        return null;
    }

    protected function parseParameter($path) {
        $parameters = array();

        preg_match('/{([A-Za-z\-\_]*?)}/is', $path, $parameters);

        if(isset($parameters[1]) && count($parameters[1]) > 0) {
            return $parameters[1];
        }

        return null;
    }

    public function matchRoute($requestMethod, $url) {

        // Check if request method is allowed
        if(count($this->requestTypes) === 0 || in_array($requestMethod, $this->requestTypes)) {

            $url = parse_url($url);
            $url = $url['path'];

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
                        $parameterValue = $url[$i];
                        $regex = (isset($this->parametersRegex[$parameter]) ? $this->parametersRegex[$parameter] : null);

                        if($regex !== null) {
                            // Use the regular expression rule provided to filter the value
                            $matches = array();
                            preg_match('/'.$regex.'/is', $url[$i], $matches);

                            if(count($matches)) {
                                $parameterValue = $matches[0];
                            }
                        }

                        // Add parameter value
                        $parameters[$parameter] = $parameterValue;
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

        $parameters = $this->parseParameters($url);

        if($parameters !== null) {
            foreach($parameters as $param) {
                $this->parameters[$param] = '';
            }
        }

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

    /**
     * Add request type
     *
     * @param $type
     * @return self
     * @throws RouterException
     */
    public function addRequestType($type) {
        if(!in_array($type, self::$allowedRequestTypes)) {
            throw new RouterException('Invalid request method: ' . $type);
        }

        $this->requestTypes[] = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequestTypes() {
        return $this->requestTypes;
    }
}
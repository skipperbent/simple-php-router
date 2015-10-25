<?php

namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

class RouterRoute extends RouterEntry {

    const PARAMETERS_REGEX_MATCH = '{([A-Za-z\-\_]*?)}';

    protected $url;

    public function __construct($url, $callback) {
        parent::__construct();
        $this->setUrl($url);
        $this->setCallback($callback);

        $this->settings['aliases'] = array();
    }

    protected function parseParameters($url, $multiple = false, $regex = self::PARAMETERS_REGEX_MATCH) {
        $url = rtrim($url, '/');
        $parameters = array();

        if($multiple) {
            preg_match_all('/'.$regex.'/is', $url, $parameters);
        } else {
            preg_match('/'.$regex.'/is', $url, $parameters);
        }

        if(isset($parameters[1]) && count($parameters[1]) > 0) {
            return $parameters[1];
        }

        return null;
    }

    public function matchRoute(Request $request) {

        // Check if request method is allowed

        $url = parse_url($request->getUri());
        $url = $url['path'];

        $route = $this->url;

        $routeMatch = preg_replace('/'.self::PARAMETERS_REGEX_MATCH.'/is', '', $route);

        // Check if url parameter count matches
        if(stripos($url, $routeMatch) === 0) {

            $matches = true;

            if($this->regexMatch) {
                $parameters = $this->parseParameters($url, true, $this->regexMatch);

                // If regex doesn't match, make sure to return an array
                if(!is_array($parameters)) {
                    $parameters = array();
                }

            } else {

                $url = explode('/', $url);
                $route = explode('/', $route);

                $parameters = array();

                // Check if url matches
                foreach ($route as $i => $path) {
                    $parameter = $this->parseParameters($path, false);

                    // Check if parameter of path matches, otherwise quit..
                    if (is_null($parameter) && strtolower($path) != strtolower($url[$i])) {
                        $matches = false;
                        break;
                    }

                    // Save parameter if we have one
                    if ($parameter) {
                        $parameterValue = $url[$i];
                        $regex = (isset($this->parametersRegex[$parameter]) ? $this->parametersRegex[$parameter] : null);

                        if ($regex !== null) {
                            // Use the regular expression rule provided to filter the value
                            $matches = array();
                            preg_match('/' . $regex . '/is', $url[$i], $matches);

                            if (count($matches)) {
                                $parameterValue = $matches[0];
                            }
                        }

                        // Add parameter value, if it doesn't exist - replace it with null value
                        $parameters[$parameter] = ($parameterValue === '') ? null : $parameterValue;
                    }
                }
            }

            // This route matches
            if($matches) {
                $this->parameters = $parameters;
                return $this;
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

        $parameters = $this->parseParameters($url, true);

        if($parameters !== null) {
            foreach($parameters as $param) {
                $this->parameters[$param] = '';
            }
        }

        $this->url = $url;
        return $this;
    }

    /**
     * Get alias for the url which can be used when getting the url route.
     * @return string
     */
    public function getAlias(){
        return $this->alias;
    }

    /**
     * Set the url alias for easier getting the url route.
     * @param string $alias
     * @return self
     */
    public function setAlias($alias){
        $this->alias = $alias;
        return $this;
    }

    public function setSettings($settings) {

        // Change as to alias
        if(isset($settings{'as'})) {
            $this->setAlias($settings['as']);
        }

        return parent::setSettings($settings);
    }

}
<?php

namespace Pecee\SimpleRouter;

use Pecee\ArrayUtil;
use Pecee\Http\Request;

class RouterRoute extends RouterEntry {

    const PARAMETERS_REGEX_MATCH = '{([A-Za-z\-\_]*?)\?{0,1}}';

    protected $url;

    public function __construct($url, $callback) {
        parent::__construct();
        $this->setUrl($url);
        $this->setCallback($callback);

        $this->settings['aliases'] = array();
    }

    public function matchRoute(Request $request) {

        $url = parse_url($request->getUri());
        $url = rtrim($url['path'], '/') . '/';

        // Match on custom defined regular expression
        if($this->regexMatch) {
            $parameters = array();
            if(preg_match('/('.$this->regexMatch.')/is', $request->getHost() . $url, $parameters)) {
                $this->parameters = (!is_array($parameters[0]) ? array($parameters[0]) : $parameters[0]);
                return true;
            }
            return null;
        }

        // Make regular expression based on route
        $route = rtrim($this->url, '/') . '/';

        $parameters = $this->parseParameters($route, $url);

        if($parameters !== null) {

            if(is_array($this->parameters)) {
                $this->parameters = array_merge($this->parameters, $parameters);
            } else {
                $this->parameters = $parameters;
            }

            return true;
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
     * @return self
     */
    public function setUrl($url) {
        $parameters = array();
        $matches = array();

        if(preg_match_all('/'.self::PARAMETERS_REGEX_MATCH.'/is', $url, $matches)) {
            $parameters = $matches[1];
        }

        if(count($parameters)) {
            $tmp = array();
            foreach($parameters as $param) {
                $tmp[$param] = null;
            }
            $this->parameters = $tmp;
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
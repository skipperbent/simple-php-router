<?php

namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

class RouterRoute extends LoadableRoute {

    public function __construct($url, $callback) {
        $this->setUrl($url);
        $this->setCallback($callback);
    }

    public function matchRoute(Request $request) {

        $url = parse_url(urldecode($request->getUri()), PHP_URL_PATH);
        $url = rtrim($url, '/') . '/';

        // Match on custom defined regular expression
        if($this->regex !== null) {
            $parameters = array();
            if(preg_match('/(' . $this->regex . ')/is', $request->getHost() . $url, $parameters)) {
                $this->parameters = (!is_array($parameters[0]) ? array($parameters[0]) : $parameters[0]);
                return true;
            }
            return null;
        }

        // Make regular expression based on route
        $route = rtrim($this->url, '/') . '/';

        $parameters = $this->parseParameters($route, $url);

        if($parameters !== null) {
            $this->parameters = array_merge($this->parameters, $parameters);
            return true;
        }

        return null;
    }

}
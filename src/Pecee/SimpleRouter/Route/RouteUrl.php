<?php
namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;

class RouteUrl extends LoadableRoute
{
    public function __construct($url, $callback)
    {
        $this->setUrl($url);
        $this->setCallback($callback);
    }

    public function matchRoute(Request $request)
    {
        $url = parse_url(urldecode($request->getUri()), PHP_URL_PATH);
        $url = rtrim($url, '/') . '/';

        // Match on custom defined regular expression
        if ($this->regex !== null) {
            $parameters = [];
            if (preg_match($this->regex, $request->getHost() . $url, $parameters)) {
                /* Remove global match */
                if (count($parameters) > 1) {
                    array_shift($parameters);
                    $this->parameters = $parameters;
                }

                return true;
            }

            return null;
        }

        // Make regular expression based on route
        $route = rtrim($this->url, '/') . '/';

        $parameters = $this->parseParameters($route, $url);

        if ($parameters !== null) {
            $this->parameters = array_merge($this->parameters, $parameters);

            return true;
        }

        return null;
    }

}
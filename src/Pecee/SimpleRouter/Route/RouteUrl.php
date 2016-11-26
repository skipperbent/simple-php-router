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

        /* Match global regular-expression for route */
        if($this->matchRegex($request, $url) === true) {
            return true;
        }

        /* Make regular expression based on route */
        $route = rtrim($this->url, '/') . '/';

        $parameters = $this->parseParameters($route, $url);

        if ($parameters !== null) {
            $this->setParameters($parameters);
            //$this->parameters = array_merge($this->parameters, $parameters);

            return true;
        }

        return false;
    }

}
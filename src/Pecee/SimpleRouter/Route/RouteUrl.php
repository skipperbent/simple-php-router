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

    public function matchRoute($url, Request $request)
    {
        $url = parse_url(urldecode($url), PHP_URL_PATH);
        $url = rtrim($url, '/') . '/';

        /* Match global regular-expression for route */
        $regexMatch = $this->matchRegex($request, $url);
        if ($regexMatch === false) {
            return false;
        }

        /* Make regular expression based on route */
        $parameters = $this->parseParameters($this->url, $url);
        if ($parameters === null) {
            return false;
        }

        $this->setParameters($parameters);

        return true;
    }

}
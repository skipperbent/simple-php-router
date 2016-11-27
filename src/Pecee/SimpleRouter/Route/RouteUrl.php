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
        $domainMatch = $this->matchRegex($request, $url);
        if($domainMatch !== null) {
            return $domainMatch;
        }

        /* Make regular expression based on route */
        $parameters = $this->parseParameters($this->url, $url);
        if($parameters !== null) {
            $this->setParameters($parameters);
            return true;
        }

        return false;

    }

}
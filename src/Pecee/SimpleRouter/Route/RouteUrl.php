<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;

/**
 * Class RouteUrl
 *
 * @package Pecee\SimpleRouter\Route
 */
class RouteUrl extends LoadableRoute
{
    /**
     * RouteUrl constructor.
     * @param $url
     * @param $callback
     */
    public function __construct($url, $callback)
    {
        $this->setUrl($url);
        $this->setCallback($callback);
    }

    /**
     * @param $url
     * @param Request $request
     * @return bool
     */
    public function matchRoute($url, Request $request): bool
    {
        if ($this->getGroup() !== null && $this->getGroup()->matchRoute($url, $request) === false) {
            return false;
        }
        /* Match global regular-expression for route */
        $regexMatch = $this->matchRegex($request, $url);

        if ($regexMatch === false) {
            return false;
        }
        /* Parse parameters from current route */
        $parameters = $this->parseParameters($this->url, $url);

        /* If no custom regular expression or parameters was found on this route, we stop */
        if ($regexMatch === null && $parameters === null) {
            return false;
        }
        /* Set the parameters */
        $this->setParameters((array)$parameters);

        return true;
    }
}
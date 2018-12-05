<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;

/**
 * Class RoutePartialGroup
 *
 * @package Pecee\SimpleRouter\Route
 */
class RoutePartialGroup extends RouteGroup implements IPartialGroupRoute
{
    /**
     * RoutePartialGroup constructor.
     */
    public function __construct()
    {
        $this->urlRegex = '/^%s\/?/u';
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

        if ($this->prefix !== null) {
            /* Parse parameters from current route */
            $parameters = $this->parseParameters($this->prefix, $url);

            /* If no custom regular expression or parameters was found on this route, we stop */
            if ($parameters === null) {
                return false;
            }

            /* Set the parameters */
            $this->setParameters((array)$parameters);
        }

        return $this->matchDomain($request);
    }
}
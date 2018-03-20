<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;

interface ILoadableRoute extends IRoute
{
    /**
     * Find url that matches method, parameters or name.
     * Used when calling the url() helper.
     *
     * @param string|null $method
     * @param array|null $parameters
     * @param string|null $name
     * @return string
     */
    public function findUrl($method = null, $parameters = null, $name = null): string;

    /**
     * Loads and renders middlewares-classes
     *
     * @param Request $request
     */
    public function loadMiddleware(Request $request);

    public function getUrl();

    public function setUrl($url);

    /**
     * Returns the provided name for the router.
     *
     * @return string|null
     */
    public function getName(): string;

    /**
     * Check if route has given name.
     *
     * @param string $name
     * @return bool
     */
    public function hasName($name): bool;

    /**
     * Sets the router name, which makes it easier to obtain the url or router at a later point.
     *
     * @param string $name
     * @return static
     */
    public function setName($name): self;

    /**
     * Get regular expression match used for matching route (if defined).
     *
     * @return string
     */
    public function getMatch(): string;

    /**
     * Add regular expression match for the entire route.
     *
     * @param string $regex
     * @return static
     */
    public function setMatch($regex): self;

}
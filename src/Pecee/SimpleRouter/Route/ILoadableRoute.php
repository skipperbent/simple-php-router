<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Router;

interface ILoadableRoute extends IRoute
{
    /**
     * Find url that matches method, parameters or name.
     * Used when calling the url() helper.
     *
     * @param string|null $method
     * @param array|string|null $parameters
     * @param string|null $name
     * @return string
     */
    public function findUrl(?string $method = null, $parameters = null, ?string $name = null): string;

    /**
     * Loads and renders middleware-classes
     *
     * @param Request $request
     * @param Router $router
     */
    public function loadMiddleware(Request $request, Router $router): void;

    /**
     * Get url
     * @return string
     */
    public function getUrl(): string;

    /**
     * Set url
     * @param string $url
     * @return static
     */
    public function setUrl(string $url): self;

    /**
     * Prepend url
     * @param string $url
     * @return ILoadableRoute
     */
    public function prependUrl(string $url): self;

    /**
     * Returns the provided name for the router.
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Check if route has given name.
     *
     * @param string $name
     * @return bool
     */
    public function hasName(string $name): bool;

    /**
     * Sets the router name, which makes it easier to obtain the url or router at a later point.
     *
     * @param string $name
     * @return static
     */
    public function setName(string $name): self;

    /**
     * Get regular expression match used for matching route (if defined).
     *
     * @return string
     */
    public function getMatch(): ?string;

    /**
     * Add regular expression match for the entire route.
     *
     * @param string $regex
     * @return static
     */
    public function setMatch($regex): self;

}
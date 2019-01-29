<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Router;

/**
 * Interface ILoadableRoute
 *
 * @package Pecee\SimpleRouter\Route
 */
interface ILoadableRoute extends IRoute
{
    /**
     * @param null|string $method
     * @param null $parameters
     * @param null|string $name
     * @return string
     */
    public function findUrl(?string $method = null, $parameters = null, ?string $name = null): string;

    /**
     * @param Request $request
     * @param Router $router
     */
    public function loadMiddleware(Request $request, Router $router): void;

    /**
     * @return string
     */
    public function getUrl(): string;

    /**
     * @param string $url
     * @return static
     */
    public function setUrl(string $url): self;

    /**
     * @param string $url
     * @return ILoadableRoute
     */
    public function prependUrl(string $url): self;

    /**
     * @return null|string
     */
    public function getName(): ?string;

    /**
     * @param string $name
     * @return bool
     */
    public function hasName(string $name): bool;

    /**
     * @param string $name
     * @return static
     */
    public function setName(string $name): self;

    /**
     * @return null|string
     */
    public function getMatch(): ?string;

    /**
     * @param $regex
     * @return ILoadableRoute
     */
    public function setMatch($regex): self;
}
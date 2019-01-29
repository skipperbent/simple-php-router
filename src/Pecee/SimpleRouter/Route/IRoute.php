<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Router;

/**
 * Interface IRoute
 *
 * @package Pecee\SimpleRouter\Route
 */
interface IRoute
{
    /**
     * @param $route
     * @param Request $request
     * @return bool
     */
    public function matchRoute($route, Request $request): bool;

    /**
     * @param Request $request
     * @param Router $router
     * @return null|string
     */
    public function renderRoute(Request $request, Router $router): ?string;

    /**
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * @param array $methods
     * @return static
     */
    public function setRequestMethods(array $methods): self;

    /**
     * @return array
     */
    public function getRequestMethods(): array;

    /**
     * @return null|IRoute
     */
    public function getParent(): ?IRoute;

    /**
     * @return null|IGroupRoute
     */
    public function getGroup(): ?IGroupRoute;

    /**
     * @param IGroupRoute $group
     * @return static
     */
    public function setGroup(IGroupRoute $group): self;

    /**
     * @param IRoute $parent
     * @return static
     */
    public function setParent(IRoute $parent): self;

    /**
     * @param $callback
     * @return IRoute
     */
    public function setCallback($callback): self;

    /**
     * @return string|callable
     */
    public function getCallback();

    /**
     * @return null|string
     */
    public function getMethod(): ?string;

    /**
     * @param string $method
     * @return static
     */
    public function setMethod(string $method): self;

    /**
     * @return null|string
     */
    public function getClass(): ?string;

    /**
     * @param string $namespace
     * @return static
     */
    public function setNamespace(string $namespace): self;

    /**
     * @return null|string
     */
    public function getNamespace(): ?string;

    /**
     * @param $namespace
     * @return static
     */
    public function setDefaultNamespace($namespace): IRoute;

    /**
     * @return null|string
     */
    public function getDefaultNamespace(): ?string;

    /**
     * @return array
     */
    public function getWhere(): array;

    /**
     * @param array $options
     * @return static
     */
    public function setWhere(array $options): self;

    /**
     * @return array
     */
    public function getParameters(): array;

    /**
     * @param array $parameters
     * @return static
     */
    public function setParameters(array $parameters): self;

    /**
     * @param array $settings
     * @param bool $merge
     * @return static
     */
    public function setSettings(array $settings, bool $merge = false): self;

    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @return array
     */
    public function getMiddlewares(): array;

    /**
     * @param $middleware
     * @return static
     */
    public function addMiddleware($middleware): self;

    /**
     * @param array $middlewares
     * @return IRoute
     */
    public function setMiddlewares(array $middlewares): self;
}
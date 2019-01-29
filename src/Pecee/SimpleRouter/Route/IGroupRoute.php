<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Handlers\IExceptionHandler;

/**
 * Interface IGroupRoute
 *
 * @package Pecee\SimpleRouter\Route
 */
interface IGroupRoute extends IRoute
{
    /**
     * @param Request $request
     * @return bool
     */
    public function matchDomain(Request $request): bool;

    /**
     * @param IExceptionHandler|string $handler
     * @return static
     */
    public function addExceptionHandler($handler): self;

    /**
     * @param array $handlers
     * @return static
     */
    public function setExceptionHandlers(array $handlers);

    /**
     * @return array
     */
    public function getExceptionHandlers(): array;

    /**
     * @return array
     */
    public function getDomains(): array;

    /**
     * @param array $domains
     * @return static
     */
    public function setDomains(array $domains): self;

    /**
     * @param $prefix
     * @return IGroupRoute
     */
    public function setPrefix($prefix): self;

    /**
     * @return null|string
     */
    public function getPrefix(): ?string;
}
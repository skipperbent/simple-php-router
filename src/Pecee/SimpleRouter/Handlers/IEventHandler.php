<?php

namespace Pecee\SimpleRouter\Handlers;

use Pecee\SimpleRouter\Router;

/**
 * Interface IEventHandler
 *
 * @package Pecee\SimpleRouter\Handlers
 */
interface IEventHandler
{
    /**
     * @param null|string $name
     * @return array
     */
    public function getEvents(?string $name): array;

    /**
     * @param Router $router
     * @param string $name
     * @param array $eventArgs
     */
    public function fireEvents(Router $router, string $name, array $eventArgs = []): void;
}
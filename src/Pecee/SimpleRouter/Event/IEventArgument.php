<?php

namespace Pecee\SimpleRouter\Event;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Router;

/**
 * Interface IEventArgument
 *
 * @package Pecee\SimpleRouter\Event
 */
interface IEventArgument
{
    /**
     * @return string
     */
    public function getEventName(): string;

    /**
     * @param string $name
     */
    public function setEventName(string $name): void;

    /**
     * @return Router
     */
    public function getRouter(): Router;

    /**
     * @return Request
     */
    public function getRequest(): Request;

    /**
     * @return array
     */
    public function getArguments(): array;
}
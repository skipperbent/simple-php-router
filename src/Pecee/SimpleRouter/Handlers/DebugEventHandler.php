<?php

namespace Pecee\SimpleRouter\Handlers;

use Pecee\SimpleRouter\Router;
use Pecee\SimpleRouter\Event\EventArgument;

class DebugEventHandler implements IEventHandler
{
    /**
     * @var \Closure
     */
    protected $callback;

    /**
     * DebugEventHandler constructor.
     */
    public function __construct()
    {
        $this->callback = function (EventArgument $argument) {
            // todo: log in database
        };
    }

    /**
     * @param null|string $name
     * @return array
     */
    public function getEvents(?string $name): array
    {
        return [
            $name => [
                $this->callback,
            ],
        ];
    }

    /**
     * @param Router $router
     * @param string $name
     * @param array $eventArgs
     */
    public function fireEvents(Router $router, string $name, array $eventArgs = []): void
    {
        $callback = $this->callback;
        $callback(new EventArgument($router, $eventArgs));
    }

    /**
     * @param \Closure $event
     */
    public function setCallback(\Closure $event): void
    {
        $this->callback = $event;
    }
}
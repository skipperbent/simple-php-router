<?php

namespace Pecee\SimpleRouter\Handlers;

use Closure;
use Pecee\SimpleRouter\Event\EventArgument;
use Pecee\SimpleRouter\Router;

class DebugEventHandler implements IEventHandler
{

    /**
     * Debug callback
     * @var Closure
     */
    protected Closure $callback;

    public function __construct()
    {
        $this->callback = static function (EventArgument $argument): void {
            // todo: log in database
        };
    }

    /**
     * Get events.
     *
     * @param string|null $name Filter events by name.
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
     * Fires any events registered with given event-name
     *
     * @param Router $router Router instance
     * @param string $name Event name
     * @param array $eventArgs Event arguments
     */
    public function fireEvents(Router $router, string $name, array $eventArgs = []): void
    {
        $callback = $this->callback;
        $callback(new EventArgument($name, $router, $eventArgs));
    }

    /**
     * Set debug callback
     *
     * @param Closure $event
     */
    public function setCallback(Closure $event): void
    {
        $this->callback = $event;
    }

}
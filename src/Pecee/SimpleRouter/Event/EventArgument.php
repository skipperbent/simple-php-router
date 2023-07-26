<?php declare(strict_types=1);

namespace Pecee\SimpleRouter\Event;

use InvalidArgumentException;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Router;

class EventArgument implements IEventArgument
{
    /**
     * Event name
     * @var string
     */
    protected $eventName;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var array
     */
    protected $arguments = [];

    public function __construct(string $eventName, Router $router, array $arguments = [])
    {
        $this->eventName = $eventName;
        $this->router = $router;
        $this->arguments = $arguments;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * Set the event name
     *
     * @param string $name
     */
    public function setEventName(string $name): void
    {
        $this->eventName = $name;
    }

    /**
     * Get the router instance
     *
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Get the request instance
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->getRouter()->getRequest();
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function __set(string $name, $value): void
    {
        throw new InvalidArgumentException('Not supported');
    }

    /**
     * Get arguments
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

}
<?php

namespace Pecee\SimpleRouter\Event;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Router;

/**
 * Class EventArgument
 *
 * @package Pecee\SimpleRouter\Event
 */
class EventArgument implements IEventArgument
{
    /**
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

    /**
     * EventArgument constructor.
     * @param $eventName
     * @param $router
     * @param array $arguments
     */
    public function __construct($eventName, $router, array $arguments = [])
    {
        $this->eventName = $eventName;
        $this->router = $router;
        $this->arguments = $arguments;
    }

    /**
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * @param string $name
     */
    public function setEventName(string $name): void
    {
        $this->eventName = $name;
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->getRouter()->getRequest();
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->arguments);
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        throw new \InvalidArgumentException('Not supported');
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
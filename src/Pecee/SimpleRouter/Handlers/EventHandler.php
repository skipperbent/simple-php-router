<?php

namespace Pecee\SimpleRouter\Handlers;

use Closure;
use Pecee\SimpleRouter\Event\EventArgument;
use Pecee\SimpleRouter\Router;

class EventHandler implements IEventHandler
{
    /**
     * Fires when a event is triggered.
     */
    public const EVENT_ALL = '*';

    /**
     * Fires when router is initializing and before routes are loaded.
     */
    public const EVENT_INIT = 'onInit';

    /**
     * Fires when all routes has been loaded and rendered, just before the output is returned.
     */
    public const EVENT_LOAD = 'onLoad';

    /**
     * Fires when route is added to the router
     */
    public const EVENT_ADD_ROUTE = 'onAddRoute';

    /**
     * Fires when a url-rewrite is and just before the routes are re-initialized.
     */
    public const EVENT_REWRITE = 'onRewrite';

    /**
     * Fires when the router is booting.
     * This happens just before boot-managers are rendered and before any routes has been loaded.
     */
    public const EVENT_BOOT = 'onBoot';

    /**
     * Fires before a boot-manager is rendered.
     */
    public const EVENT_RENDER_BOOTMANAGER = 'onRenderBootManager';

    /**
     * Fires when the router is about to load all routes.
     */
    public const EVENT_LOAD_ROUTES = 'onLoadRoutes';

    /**
     * Fires whenever the `findRoute` method is called within the `Router`.
     * This usually happens when the router tries to find routes that
     * contains a certain url, usually after the EventHandler::EVENT_GET_URL event.
     */
    public const EVENT_FIND_ROUTE = 'onFindRoute';

    /**
     * Fires whenever the `Router::getUrl` method or `url`-helper function
     * is called and the router tries to find the route.
     */
    public const EVENT_GET_URL = 'onGetUrl';

    /**
     * Fires when a route is matched and valid (correct request-type etc).
     * and before the route is rendered.
     */
    public const EVENT_MATCH_ROUTE = 'onMatchRoute';

    /**
     * Fires before a route is rendered.
     */
    public const EVENT_RENDER_ROUTE = 'onRenderRoute';

    /**
     * Fires when the router is loading exception-handlers.
     */
    public const EVENT_LOAD_EXCEPTIONS = 'onLoadExceptions';

    /**
     * Fires before the router is rendering a exception-handler.
     */
    public const EVENT_RENDER_EXCEPTION = 'onRenderException';

    /**
     * Fires before a middleware is rendered.
     */
    public const EVENT_RENDER_MIDDLEWARES = 'onRenderMiddlewares';

    /**
     * Fires before the CSRF-verifier is rendered.
     */
    public const EVENT_RENDER_CSRF = 'onRenderCsrfVerifier';

    /**
     * All available events
     * @var array
     */
    public static array $events = [
        self::EVENT_ALL,
        self::EVENT_INIT,
        self::EVENT_LOAD,
        self::EVENT_ADD_ROUTE,
        self::EVENT_REWRITE,
        self::EVENT_BOOT,
        self::EVENT_RENDER_BOOTMANAGER,
        self::EVENT_LOAD_ROUTES,
        self::EVENT_FIND_ROUTE,
        self::EVENT_GET_URL,
        self::EVENT_MATCH_ROUTE,
        self::EVENT_RENDER_ROUTE,
        self::EVENT_LOAD_EXCEPTIONS,
        self::EVENT_RENDER_EXCEPTION,
        self::EVENT_RENDER_MIDDLEWARES,
        self::EVENT_RENDER_CSRF,
    ];

    /**
     * List of all registered events
     * @var array
     */
    private array $registeredEvents = [];

    /**
     * Register new event
     *
     * @param string $name
     * @param Closure $callback
     * @return static
     */
    public function register(string $name, Closure $callback): IEventHandler
    {
        if (isset($this->registeredEvents[$name]) === true) {
            $this->registeredEvents[$name][] = $callback;
        } else {
            $this->registeredEvents[$name] = [$callback];
        }

        return $this;
    }

    /**
     * Get events.
     *
     * @param string|null $name Filter events by name.
     * @param array|string ...$names Add multiple names...
     * @return array
     */
    public function getEvents(?string $name, ...$names): array
    {
        if ($name === null) {
            return $this->registeredEvents;
        }

        $names[] = $name;
        $events = [];

        foreach ($names as $eventName) {
            if (isset($this->registeredEvents[$eventName]) === true) {
                $events += $this->registeredEvents[$eventName];
            }
        }

        return $events;
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
        $events = $this->getEvents(static::EVENT_ALL, $name);

        /* @var $event Closure */
        foreach ($events as $event) {
            $event(new EventArgument($name, $router, $eventArgs));
        }
    }

}
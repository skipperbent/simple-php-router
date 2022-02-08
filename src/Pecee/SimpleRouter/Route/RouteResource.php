<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;

class RouteResource extends LoadableRoute implements IControllerRoute
{
    protected array $urls = [
        'index'   => '',
        'create'  => 'create',
        'store'   => '',
        'show'    => '',
        'edit'    => 'edit',
        'update'  => '',
        'destroy' => '',
    ];

    protected array $methodNames = [
        'index'   => 'index',
        'create'  => 'create',
        'store'   => 'store',
        'show'    => 'show',
        'edit'    => 'edit',
        'update'  => 'update',
        'destroy' => 'destroy',
    ];

    protected array $names = [];
    protected string $controller;

    /**
     * @param string $url
     * @param string $controller
     */
    public function __construct(string $url, string $controller)
    {
        $this->setUrl($url);
        $this->controller = $controller;
        $this->setName(trim(str_replace('/', '.', $url), '/'));
    }

    /**
     * Check if route has given name.
     *
     * @param string $name
     * @return bool
     */
    public function hasName(string $name): bool
    {
        if ($this->getName() === null) {
            return false;
        }

        if (strtolower($this->getName()) === strtolower($name)) {
            return true;
        }

        /* Remove method/type */
        if (strpos($name, '.') !== false) {
            $name = substr($name, 0, strrpos($name, '.'));
        }

        return (strtolower($this->getName()) === strtolower($name));
    }

    /**
     * @param string|null $method
     * @param array|string|null $parameters
     * @param string|null $name
     * @return string
     */
    public function findUrl(?string $method = null, $parameters = null, ?string $name = null): string
    {
        $url = array_search($name, $this->names, true);
        if ($url !== false) {
            return rtrim($this->url . $this->urls[$url], '/') . '/';
        }

        return $this->url;
    }

    protected function call(string $method): bool
    {
        $this->setCallback([$this->controller, $method]);

        return true;
    }

    public function matchRoute(string $url, Request $request): bool
    {
        if ($this->matchGroup($url, $request) === false) {
            return false;
        }

        /* Match global regular-expression for route */
        $regexMatch = $this->matchRegex($request, $url);

        if ($regexMatch === false || (stripos($url, $this->url) !== 0 && strtoupper($url) !== strtoupper($this->url))) {
            return false;
        }

        $route = rtrim($this->url, '/') . '/{id?}/{action?}';

        /* Parse parameters from current route */
        $this->parameters = $this->parseParameters($route, $url);

        /* If no custom regular expression or parameters was found on this route, we stop */
        if ($regexMatch === null && $this->getParameters() === null) {
            return false;
        }

        $action = strtolower(trim($this->getParameters()['action']));
        $id = $this->getParameters()['id'];

        // Remove action parameter
        unset($this->getParameters()['action']);

        $method = $request->getMethod();

        // Delete
        if ($method === Request::REQUEST_TYPE_DELETE && $id !== null) {
            return $this->call($this->getMethodNames()['destroy']);
        }

        // Update
        if ($id !== null && in_array($method, [Request::REQUEST_TYPE_PATCH, Request::REQUEST_TYPE_PUT], true) === true) {
            return $this->call($this->getMethodNames()['update']);
        }

        // Edit
        if ($method === Request::REQUEST_TYPE_GET && $id !== null && $action === 'edit') {
            return $this->call($this->getMethodNames()['edit']);
        }

        // Create
        if ($method === Request::REQUEST_TYPE_GET && $id === 'create') {
            return $this->call($this->getMethodNames()['create']);
        }

        // Save
        if ($method === Request::REQUEST_TYPE_POST) {
            return $this->call($this->getMethodNames()['store']);
        }

        // Show
        if ($method === Request::REQUEST_TYPE_GET && $id !== null) {
            return $this->call($this->getMethodNames()['show']);
        }

        // Index
        return $this->call($this->getMethodNames()['index']);
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @param string $controller
     * @return static
     */
    public function setController(string $controller): IControllerRoute
    {
        $this->controller = $controller;

        return $this;
    }

    public function setName(string $name): ILoadableRoute
    {
        $this->name = $name;

        $this->names = [
            'index'   => $this->name . '.index',
            'create'  => $this->name . '.create',
            'store'   => $this->name . '.store',
            'show'    => $this->name . '.show',
            'edit'    => $this->name . '.edit',
            'update'  => $this->name . '.update',
            'destroy' => $this->name . '.destroy',
        ];

        return $this;
    }

    /**
     * Define custom method name for resource controller
     *
     * @param array $names
     * @return static $this
     */
    public function setMethodNames(array $names): RouteResource
    {
        $this->methodNames = $names;

        return $this;
    }

    /**
     * Get method names
     *
     * @return array
     */
    public function getMethodNames(): array
    {
        return $this->methodNames;
    }

    /**
     * Merge with information from another route.
     *
     * @param array $settings
     * @param bool $merge
     * @return static
     */
    public function setSettings(array $settings, bool $merge = false): IRoute
    {
        if (isset($settings['names']) === true) {
            $this->names = $settings['names'];
        }

        if (isset($settings['methods']) === true) {
            $this->methodNames = $settings['methods'];
        }

        return parent::setSettings($settings, $merge);
    }

}
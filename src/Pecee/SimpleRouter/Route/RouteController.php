<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;

class RouteController extends LoadableRoute implements IControllerRoute
{
    protected string $defaultMethod = 'index';
    protected string $controller;
    protected ?string $method = null;
    protected array $names = [];

    public function __construct($url, $controller)
    {
        $this->setUrl($url);
        $this->setName(trim(str_replace('/', '.', $url), '/'));
        $this->controller = $controller;
    }

    /**
     * Check if route has given name.
     *
     * @param string $name
     * @return bool
     */
    public function hasName(string $name): bool
    {
        if ($this->name === null) {
            return false;
        }

        /* Remove method/type */
        if (strpos($name, '.') !== false) {
            $method = substr($name, strrpos($name, '.') + 1);
            $newName = substr($name, 0, strrpos($name, '.'));

            if (in_array($method, $this->names, true) === true && strtolower($this->name) === strtolower($newName)) {
                return true;
            }
        }

        return parent::hasName($name);
    }

    /**
     * @param string|null $method
     * @param string|array|null $parameters
     * @param string|null $name
     * @return string
     */
    public function findUrl(?string $method = null, $parameters = null, ?string $name = null): string
    {
        if (strpos($name, '.') !== false) {
            $found = array_search(substr($name, strrpos($name, '.') + 1), $this->names, true);
            if ($found !== false) {
                $method = (string)$found;
            }
        }

        $url = '';
        $parameters = (array)$parameters;

        if ($method !== null) {

            /* Remove requestType from method-name, if it exists */
            foreach (Request::$requestTypes as $requestType) {

                if (stripos($method, $requestType) === 0) {
                    $method = substr($method, strlen($requestType));
                    break;
                }
            }

            $method .= '/';
        }

        $group = $this->getGroup();

        if ($group !== null && count($group->getDomains()) !== 0) {
            $url .= '//' . $group->getDomains()[0];
        }

        $url .= '/' . trim($this->getUrl(), '/') . '/' . strtolower((string)$method) . implode('/', $parameters);

        return '/' . trim($url, '/') . '/';
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

        $strippedUrl = trim(str_ireplace($this->url, '/', $url), '/');
        $path = explode('/', $strippedUrl);

        if (count($path) !== 0) {

            $method = (isset($path[0]) === false || trim($path[0]) === '') ? $this->defaultMethod : $path[0];
            $this->method = $request->getMethod() . ucfirst($method);

            $this->parameters = array_slice($path, 1);

            // Set callback
            $this->setCallback([$this->controller, $this->method]);

            return true;
        }

        return false;
    }

    /**
     * Get controller class-name.
     *
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * Get controller class-name.
     *
     * @param string $controller
     * @return static
     */
    public function setController(string $controller): IControllerRoute
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * Return active method
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Set active method
     *
     * @param string $method
     * @return static
     */
    public function setMethod(string $method): IRoute
    {
        $this->method = $method;

        return $this;
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

        return parent::setSettings($settings, $merge);
    }

}
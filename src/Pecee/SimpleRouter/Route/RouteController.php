<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;

/**
 * Class RouteController
 * @package Pecee\SimpleRouter\Route
 */
class RouteController extends LoadableRoute implements IControllerRoute
{
    protected $defaultMethod = 'index';
    protected $controller;
    protected $method;
    protected $names = [];

    /**
     * RouteController constructor.
     * @param $url
     * @param $controller
     */
    public function __construct($url, $controller)
    {
        $this->setUrl($url);
        $this->setName(trim(str_replace('/', '.', $url), '/'));
        $this->controller = $controller;
    }

    /**
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

            if (\in_array($method, $this->names, true) === true && strtolower($this->name) === strtolower($newName)) {
                return true;
            }
        }

        return parent::hasName($name);
    }

    /**
     * @param null|string $method
     * @param null $parameters
     * @param null|string $name
     * @return string
     */
    public function findUrl(?string $method = null, $parameters = null, ?string $name = null): string
    {
        if (strpos($name, '.') !== false) {
            $found = array_search(substr($name, strrpos($name, '.') + 1), $this->names, false);
            if ($found !== false) {
                $method = (string)$found;
            }
        }

        $url = '';
        $parameters = (array)$parameters;

        if ($method !== null) {

            /* Remove requestType from method-name, if it exists */
            foreach (static::$requestTypes as $requestType) {

                if (stripos($method, $requestType) === 0) {
                    $method = (string)substr($method, \strlen($requestType));
                    break;
                }
            }

            $method .= '/';
        }

        $group = $this->getGroup();

        if ($group !== null && \count($group->getDomains()) !== 0) {
            $url .= '//' . $group->getDomains()[0];
        }

        $url .= '/' . trim($this->getUrl(), '/') . '/' . strtolower($method) . implode('/', $parameters);

        return '/' . trim($url, '/') . '/';
    }

    /**
     * @param $url
     * @param Request $request
     * @return bool
     */
    public function matchRoute($url, Request $request): bool
    {
        if ($this->getGroup() !== null && $this->getGroup()->matchRoute($url, $request) === false) {
            return false;
        }

        /* Match global regular-expression for route */
        $regexMatch = $this->matchRegex($request, $url);

        if ($regexMatch === false || (stripos($url, $this->url) !== 0 && strtoupper($url) !== strtoupper($this->url))) {
            return false;
        }

        $strippedUrl = trim(str_ireplace($this->url, '/', $url), '/');
        $path = explode('/', $strippedUrl);

        if (\count($path) !== 0) {

            $method = (isset($path[0]) === false || trim($path[0]) === '') ? $this->defaultMethod : $path[0];
            $this->method = $request->getMethod() . ucfirst($method);

            $this->parameters = \array_slice($path, 1);

            // Set callback
            $this->setCallback($this->controller . '@' . $this->method);

            return true;
        }

        return false;
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

    /**
     * @return null|string
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return static
     */
    public function setMethod(string $method): IRoute
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param array $values
     * @param bool $merge
     * @return static
     */
    public function setSettings(array $values, bool $merge = false): IRoute
    {
        if (isset($values['names']) === true) {
            $this->names = $values['names'];
        }

        return parent::setSettings($values, $merge);
    }
}
<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;

class RoutePartialGroup extends RouteGroup implements IPartialGroupRoute
{

    protected $strictMatch = false;

    /**
     * RoutePartialGroup constructor.
     */
    public function __construct()
    {
        $this->urlRegex = '/^%s\/?/u';
    }

    /**
     * Method called to check if route matches
     *
     * @param string $url
     * @param Request $request
     * @return bool
     */
    public function matchRoute($url, Request $request): bool
    {
        if ($this->getGroup() !== null && $this->getGroup()->matchRoute($url, $request) === false) {
            return false;
        }

        if ($this->prefix !== null) {
            /* Parse parameters from current route */
            $parameters = $this->parseParameters($this->prefix, $url);

            /* If no custom regular expression or parameters was found on this route, we stop */
            if ($parameters === null) {
                return false;
            }

            /* Set the parameters */
            $this->setParameters($parameters);

            if($this->strictMatch === true) {
                return (trim($this->prefix, '/') === trim($url, '/'));
            }
        }

        return $this->matchDomain($request);
    }

    /**
     * Enable or disable strict matching
     * @param bool $enabled
     * @return static $this
     */
    public function setStrictMatch(bool $enabled): self
    {
        $this->strictMatch = $enabled;
        return $this;
    }

    /**
     * Return true if strict-match is enabled
     * @return bool
     */
    public function getStrictMatch(): bool
    {
        return $this->strictMatch;
    }

    /**
     * Merge with information from another route.
     *
     * @param array $values
     * @param bool $merge
     * @return static
     */
    public function setSettings(array $values, bool $merge = false): IRoute
    {
        if (isset($values['strict']) === true) {
            $this->setStrictMatch($values['strict']);
        }

        return parent::setSettings($values, $merge);
    }

}
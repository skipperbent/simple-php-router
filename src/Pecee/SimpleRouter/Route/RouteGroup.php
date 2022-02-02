<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Handlers\IExceptionHandler;

class RouteGroup extends Route implements IGroupRoute
{
    protected $urlRegex = '/^%s\/?/u';
    protected $prefix;
    protected $name;
    protected $domains = [];
    protected $exceptionHandlers = [];
    protected $mergeExceptionHandlers = true;

    /**
     * Method called to check if a domain matches
     *
     * @param Request $request
     * @return bool
     */
    public function matchDomain(Request $request): bool
    {
        if ($this->domains === null || count($this->domains) === 0) {
            return true;
        }

        foreach ($this->domains as $domain) {

            // If domain has no parameters but matches
            if ($domain === $request->getHost()) {
                return true;
            }

            $parameters = $this->parseParameters($domain, $request->getHost(), '.*');

            if ($parameters !== null && count($parameters) !== 0) {
                $this->parameters = $parameters;

                return true;
            }
        }

        return false;
    }

    /**
     * Method called to check if route matches
     *
     * @param string $url
     * @param Request $request
     * @return bool
     */
    public function matchRoute(string $url, Request $request): bool
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
        }

        $parsedPrefix = $this->prefix;

        foreach ($this->getParameters() as $parameter => $value) {
            $parsedPrefix = str_ireplace('{' . $parameter . '}', $value, $parsedPrefix);
        }

        /* Skip if prefix doesn't match */
        if ($this->prefix !== null && stripos($url, rtrim($parsedPrefix, '/') . '/') === false) {
            return false;
        }

        return $this->matchDomain($request);
    }

    /**
     * Add exception handler
     *
     * @param IExceptionHandler|string $handler
     * @return static
     */
    public function addExceptionHandler($handler): IGroupRoute
    {
        $this->exceptionHandlers[] = $handler;

        return $this;
    }

    /**
     * Set exception-handlers for group
     *
     * @param array $handlers
     * @return static
     */
    public function setExceptionHandlers(array $handlers): IGroupRoute
    {
        $this->exceptionHandlers = $handlers;

        return $this;
    }

    /**
     * Get exception-handlers for group
     *
     * @return array
     */
    public function getExceptionHandlers(): array
    {
        return $this->exceptionHandlers;
    }

    /**
     * Get allowed domains for domain.
     *
     * @return array
     */
    public function getDomains(): array
    {
        return $this->domains;
    }

    /**
     * Set allowed domains for group.
     *
     * @param array $domains
     * @return static
     */
    public function setDomains(array $domains): IGroupRoute
    {
        $this->domains = $domains;

        return $this;
    }

    /**
     * @param string $prefix
     * @return static
     */
    public function setPrefix(string $prefix): IGroupRoute
    {
        $this->prefix = '/' . trim($prefix, '/');

        return $this;
    }

    /**
     * Prepends prefix while ensuring that the url has the correct formatting.
     *
     * @param string $url
     * @return static
     */
    public function prependPrefix(string $url): IGroupRoute
    {
        return $this->setPrefix(rtrim($url, '/') . $this->prefix);
    }

    /**
     * Set prefix that child-routes will inherit.
     *
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * When enabled group will overwrite any existing exception-handlers.
     *
     * @param bool $merge
     * @return static
     */
    public function setMergeExceptionHandlers(bool $merge): IGroupRoute
    {
        $this->mergeExceptionHandlers = $merge;

        return $this;
    }

    /**
     * Returns true if group should overwrite existing exception-handlers.
     *
     * @return bool
     */
    public function getMergeExceptionHandlers(): bool
    {
        return $this->mergeExceptionHandlers;
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
        if (isset($settings['prefix']) === true) {
            $this->setPrefix($settings['prefix'] . $this->prefix);
        }

        if (isset($settings['mergeExceptionHandlers']) === true) {
            $this->setMergeExceptionHandlers($settings['mergeExceptionHandlers']);
        }

        if ($merge === false && isset($settings['exceptionHandler']) === true) {
            $this->setExceptionHandlers((array)$settings['exceptionHandler']);
        }

        if ($merge === false && isset($settings['domain']) === true) {
            $this->setDomains((array)$settings['domain']);
        }

        if (isset($settings['as']) === true) {

            $name = $settings['as'];

            if ($this->name !== null && $merge !== false) {
                $name .= '.' . $this->name;
            }

            $this->name = $name;
        }

        return parent::setSettings($settings, $merge);
    }

    /**
     * Export route settings to array so they can be merged with another route.
     *
     * @return array
     */
    public function toArray(): array
    {
        $values = [];

        if ($this->prefix !== null) {
            $values['prefix'] = $this->getPrefix();
        }

        if ($this->name !== null) {
            $values['as'] = $this->name;
        }

        if (count($this->parameters) !== 0) {
            $values['parameters'] = $this->parameters;
        }

        return array_merge($values, parent::toArray());
    }

}
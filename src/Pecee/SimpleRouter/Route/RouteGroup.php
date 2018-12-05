<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Handlers\IExceptionHandler;

/**
 * Class RouteGroup
 *
 * @package Pecee\SimpleRouter\Route
 */
class RouteGroup extends Route implements IGroupRoute
{
    protected $prefix;
    protected $name;
    protected $domains = [];
    protected $exceptionHandlers = [];

    /**
     * @param Request $request
     * @return bool
     */
    public function matchDomain(Request $request): bool
    {
        if ($this->domains === null || \count($this->domains) === 0) {
            return true;
        }

        foreach ($this->domains as $domain) {
            $parameters = $this->parseParameters($domain, $request->getHost(), '.*');
            if ($parameters !== null && \count($parameters) !== 0) {

                $this->parameters = $parameters;

                return true;
            }
        }

        return false;
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

        /* Skip if prefix doesn't match */
        if ($this->prefix !== null && stripos($url, $this->prefix) === false) {
            return false;
        }

        return $this->matchDomain($request);
    }

    /**
     * @param IExceptionHandler|string $handler
     * @return IGroupRoute
     */
    public function addExceptionHandler($handler): IGroupRoute
    {
        $this->exceptionHandlers[] = $handler;

        return $this;
    }

    /**
     * @param array $handlers
     * @return IGroupRoute
     */
    public function setExceptionHandlers(array $handlers): IGroupRoute
    {
        $this->exceptionHandlers = $handlers;

        return $this;
    }

    /**
     * @return array
     */
    public function getExceptionHandlers(): array
    {
        return $this->exceptionHandlers;
    }

    /**
     * @return array
     */
    public function getDomains(): array
    {
        return $this->domains;
    }

    /**
     * @param array $domains
     * @return IGroupRoute
     */
    public function setDomains(array $domains): IGroupRoute
    {
        $this->domains = $domains;

        return $this;
    }

    /**
     * @param $prefix
     * @return IGroupRoute
     */
    public function setPrefix($prefix): IGroupRoute
    {
        $this->prefix = '/' . trim($prefix, '/');

        return $this;
    }

    /**
     * @return null|string
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @param array $values
     * @param bool $merge
     * @return IRoute
     */
    public function setSettings(array $values, bool $merge = false): IRoute
    {

        if (isset($values['prefix']) === true) {
            $this->setPrefix($values['prefix'] . $this->prefix);
        }

        if ($merge === false && isset($values['exceptionHandler']) === true) {
            $this->setExceptionHandlers((array)$values['exceptionHandler']);
        }

        if ($merge === false && isset($values['domain']) === true) {
            $this->setDomains((array)$values['domain']);
        }

        if (isset($values['as']) === true) {

            $name = $values['as'];

            if ($this->name !== null && $merge !== false) {
                $name .= '.' . $this->name;
            }

            $this->name = $name;
        }

        return parent::setSettings($values, $merge);
    }

    /**
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

        if (\count($this->parameters) !== 0) {
            $values['parameters'] = $this->parameters;
        }

        return array_merge($values, parent::toArray());
    }
}
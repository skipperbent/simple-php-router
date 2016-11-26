<?php
namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;

class RouteGroup extends Route implements IGroupRoute
{
    protected $prefix;
    protected $name;
    protected $domains = [];
    protected $exceptionHandlers = [];

    /**
     * Method called to check if a domain matches
     *
     * @param Request $request
     * @return bool
     */
    public function matchDomain(Request $request)
    {
        if (count($this->domains) > 0) {

            $max = count($this->domains) - 1;

            for ($i = $max; $i >= 0; $i--) {

                $domain = $this->domains[$i];
                $parameters = $this->parseParameters($domain, $request->getHost(), '.*');

                if ($parameters !== null && count($parameters) > 0) {
                    $this->parameters = array_merge($this->parameters, $parameters);

                    return true;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Method called to check if route matches
     *
     * @param Request $request
     * @return bool
     */
    public function matchRoute(Request $request)
    {
        // Skip if prefix doesn't match
        if ($this->prefix !== null && stripos($request->getUri(), $this->prefix) === false) {
            return false;
        }

        return $this->matchDomain($request);
    }

    /**
     * Set exception-handlers for group
     *
     * @param array $handlers
     * @return static $this
     */
    public function setExceptionHandlers(array $handlers)
    {
        $this->exceptionHandlers = $handlers;

        return $this;
    }

    /**
     * Get exception-handlers for group
     *
     * @return array
     */
    public function getExceptionHandlers()
    {
        return $this->exceptionHandlers;
    }

    /**
     * Get allowed domains for domain.
     *
     * @return array
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * Set allowed domains for group.
     *
     * @param array $domains
     * @return $this
     */
    public function setDomains(array $domains)
    {
        $this->domains = $domains;

        return $this;
    }

    /**
     * @param string $prefix
     * @return static
     */
    public function setPrefix($prefix)
    {
        $this->prefix = '/' . trim($prefix, '/');

        return $this;
    }

    /**
     * Set prefix that child-routes will inherit.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Merge with information from another route.
     *
     * @param array $values
     * @param bool $merge
     * @return static
     */
    public function setSettings(array $values, $merge = false)
    {

        if (isset($values['prefix'])) {
            $this->setPrefix($values['prefix'] . $this->prefix);
        }

        if (isset($values['exceptionHandler'])) {
            $this->setExceptionHandlers((array)$values['exceptionHandler']);
        }

        if (isset($values['domain'])) {
            $this->setDomains((array)$values['domain']);
        }

        if (isset($values['as'])) {
            if ($this->name !== null && $merge !== false) {
                $this->name = $values['as'] . '.' . $this->name;
            } else {
                $this->name = $values['as'];
            }
        }

        parent::setSettings($values, $merge);

        return $this;
    }

    /**
     * Export route settings to array so they can be merged with another route.
     *
     * @return array
     */
    public function toArray()
    {
        $values = [];

        if ($this->prefix !== null) {
            $values['prefix'] = $this->getPrefix();
        }

        if ($this->name !== null) {
            $values['as'] = $this->name;
        }

        return array_merge($values, parent::toArray());
    }

}
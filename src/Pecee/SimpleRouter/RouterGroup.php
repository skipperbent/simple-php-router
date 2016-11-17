<?php

namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

class RouterGroup extends RouterEntry {

    protected $prefix;
    protected $domains = array();
    protected $exceptionHandlers = array();

    public function matchDomain(Request $request) {
        if(count($this->domains)) {
            for($i = 0; $i < count($this->domains); $i++) {
                $domain = $this->domains[$i];

                $parameters = $this->parseParameters($domain, $request->getHost(), '.*');

                if($parameters !== null) {
                    $this->parameters = $parameters;
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    public function matchRoute(Request $request) {
        // Skip if prefix doesn't match
        if($this->prefix !== null && stripos($request->getUri(), $this->prefix) === false) {
            return false;
        }

        return $this->matchDomain($request);
    }

    public function setExceptionHandlers(array $handlers) {
        $this->exceptionHandlers = $handlers;
        return $this;
    }

    public function getExceptionHandlers() {
        return $this->exceptionHandlers;
    }

    public function getDomains() {
        return $this->domains;
    }

    public function setDomains(array $domains) {
        $this->domains = $domains;
        return $this;
    }

    /**
     * @param string $prefix
     * @return static
     */
    public function setPrefix($prefix) {
        $this->prefix = '/' . trim($prefix, '/');
        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix() {
        return $this->prefix;
    }

    public function setData(array $settings) {

        if(isset($settings['prefix'])) {
            $this->setPrefix($settings['prefix']);
        }

        if(isset($settings['exceptionHandler'])) {
            $handlers = is_array($settings['exceptionHandler']) ? $settings['exceptionHandler'] : array($settings['exceptionHandler']);
            $this->setExceptionHandlers($handlers);
        }

        if(isset($settings['domain'])) {
            $domains = is_array($settings['domain']) ? $settings['domain'] : array($settings['domain']);
            $this->setDomains($domains);
        }

        return parent::setData($settings);
    }

}
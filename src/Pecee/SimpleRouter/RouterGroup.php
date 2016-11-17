<?php

namespace Pecee\SimpleRouter;

use Pecee\Exception\RouterException;
use Pecee\Http\Request;

class RouterGroup extends RouterEntry {

    public function __construct() {
        $this->settings = array_merge($this->settings, [
            'exceptionHandlers' => array()
        ]);
    }

    public function matchDomain(Request $request) {
        if($this->getSetting('domain') !== null) {

            if(is_array($this->getSetting('domain'))) {

                for($i = 0; $i < count($this->getSetting('domain')); $i++) {
                    $domain = $this->settings['domain'][$i];

                    $parameters = $this->parseParameters($domain, $request->getHost(), '[^.]*');

                    if($parameters !== null) {
                        $this->settings['parameters'] = $parameters;
                        return true;
                    }
                }

                return false;
            }

            $parameters = $this->parseParameters($this->getSetting('domain'), $request->getHost(), '[^.]*');

            if ($parameters !== null) {
                $this->settings['parameters'] = $parameters;
                return true;
            }

            return false;
        }

        return true;
    }

    public function renderRoute(Request $request) {
        // Check if request method is allowed
        $hasAccess = true;

        if($this->getSetting('method') !== null) {
            if(is_array($this->getSetting('method'))) {
                $hasAccess = (in_array($request->getMethod(), $this->getRequestMethods()));
            } else {
                $hasAccess = strtolower($this->getRequestMethods()) == strtolower($request->getMethod());
            }
        }

        if(!$hasAccess) {
            throw new RouterException('Method not allowed');
        }

        $this->matchDomain($request);

        return parent::renderRoute($request);
    }

    public function matchRoute(Request $request) {
        // Skip if prefix doesn't match
        if($this->getPrefix() !== null && stripos($request->getUri(), $this->getPrefix()) === false) {
            return false;
        }

        return $this->matchDomain($request);
    }

    public function setExceptionHandlers($class) {
        $this->settings['exceptionHandlers'][] = $class;
        return $this;
    }

    public function getExceptionHandlers() {
        return $this->getSetting('exceptionHandlers');
    }

    public function getDomain() {
        return $this->getSetting('domain');
    }

    /**
     * @param string $prefix
     * @return static
     */
    public function setPrefix($prefix) {
        $this->settings['prefix'] = '/' . trim($prefix, '/');
        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix() {
        return $this->getSetting('prefix');
    }

    /**
     * @param array $settings
     * @return static
     */
    public function addSettings(array $settings) {

        if ($this->getNamespace() !== null && isset($settings['namespace'])) {
            unset($settings['namespace']);
        }

        // Push middleware if multiple
        if ($this->getMiddleware() !== null && isset($settings['middleware'])) {

            if (!is_array($settings['middleware'])) {
                $settings['middleware'] = array_merge($this->getMiddleware(), array($settings['middleware']));
            } else {
                $settings['middleware'][] = $this->getMiddleware();
            }

            $settings['middleware'] = array_unique(array_reverse($settings['middleware']));
        }

        if(isset($settings['prefix'])) {
            $this->setPrefix($settings['prefix']);
            unset($settings['prefix']);
        }

        $this->settings = array_merge($this->settings, $settings);
        return $this;
    }

    public function getMergeableSettings() {
        $settings = $this->settings;

        if(isset($settings['prefix'])) {
            unset($settings['prefix']);
        }

        return $settings;
    }

}
<?php

namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

class RouterRoute extends RouterEntry implements ILoadableRoute {

    const PARAMETERS_REGEX_MATCH = '{([A-Za-z\-\_]*?)\?{0,1}}';

    protected $url;

    public function __construct($url, $callback) {
        $this->setUrl($url);
        $this->setCallback($callback);
    }

    public function matchRoute(Request $request) {

        $url = parse_url(urldecode($request->getUri()), PHP_URL_PATH);
        $url = rtrim($url, '/') . '/';

        // Match on custom defined regular expression
        if($this->setting('regexMatch') !== null) {
            $parameters = array();
            if(preg_match('/(' . $this->setting('regexMatch') . ')/is', $request->getHost() . $url, $parameters)) {
                $this->settings['parameters'] = (!is_array($parameters[0]) ? array($parameters[0]) : $parameters[0]);
                return true;
            }
            return null;
        }

        // Make regular expression based on route
        $route = rtrim($this->url, '/') . '/';

        $parameters = $this->parseParameters($route, $url);

        if($parameters !== null) {
            $this->settings['parameters'] = array_merge($this->settingArray('parameters'), $parameters);
            return true;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @param string $url
     * @return static
     */
    public function setUrl($url) {
        $parameters = array();
        $matches = array();

        if(preg_match_all('/' . static::PARAMETERS_REGEX_MATCH . '/is', $url, $matches)) {
            $parameters = $matches[1];
        }

        if(count($parameters)) {

            foreach(array_keys($parameters) as $key) {
                $parameters[$key] = null;
            }

            $this->settings['parameters'] = $parameters;
        }

        $this->url = $url;
        return $this;
    }

    /**
     * Get alias for the url which can be used when getting the url route.
     * @return string|array
     */
    public function getAlias(){
        return $this->setting('alias');
    }

    /**
     * Check if route has given alias.
     *
     * @param string $name
     * @return bool
     */
    public function hasAlias($name) {
        if ($this->getAlias() !== null) {
            if (is_array($this->getAlias())) {
                foreach ($this->setting('alias') as $alias) {
                    if (strtolower($alias) === strtolower($name)) {
                        return true;
                    }
                }
            }
            return strtolower($this->getAlias()) === strtolower($name);
        }

        return false;
    }

    /**
     * Set the url alias for easier getting the url route.
     * @param string|array $alias
     * @return static
     */
    public function setAlias($alias){
        $this->settings['alias'] = $alias;
        return $this;
    }

    public function addSettings(array $settings) {

        // Change as to alias
        if(isset($settings['as'])) {
            $this->setAlias($settings['as']);
        }

        return parent::addSettings($settings);
    }

}
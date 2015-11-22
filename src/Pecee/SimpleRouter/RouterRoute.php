<?php

namespace Pecee\SimpleRouter;

use Pecee\Http\Request;

class RouterRoute extends RouterEntry {

    const PARAMETERS_REGEX_MATCH = '{([A-Za-z\-\_]*?)\?{0,1}}';

    protected $url;

    public function __construct($url, $callback) {
        parent::__construct();
        $this->setUrl($url);
        $this->setCallback($callback);

        $this->settings['aliases'] = array();
    }

    public function matchRoute(Request $request) {

        $url = parse_url($request->getUri());
        $url = rtrim($url['path'], '/') . '/';

        // Match on custom defined regular expression
        if($this->regexMatch) {
            $parameters = array();
            if(preg_match('/('.$this->regexMatch.')/is', $url, $parameters)) {
                $this->parameters = (!is_array($parameters[0]) ? array($parameters[0]) : $parameters[0]);
                return $this;
            }
        }

        // Make regular expression based on route

        $route = rtrim($this->url, '/') . '/';

        $parameterNames = array();
        $regex = '';
        $lastCharacter = '';
        $isParameter = false;
        $parameter = '';

        for($i = 0; $i < strlen($route); $i++) {

            $character = $route[$i];

            // Skip "/" if we are at the end of a parameter
            if($lastCharacter === '}' && $character === '/') {
                $lastCharacter = $character;
                continue;
            }

            if($character === '{') {
                // Remove "/" and "\" from regex
                if(substr($regex, strlen($regex)-1) === '/') {
                    $regex = substr($regex, 0, strlen($regex) - 2);
                }

                $isParameter = true;
            } elseif($isParameter && $character === '}') {
                $required = true;
                // Check for optional parameter
                if($lastCharacter === '?') {
                    $parameter = substr($parameter, 0, strlen($parameter)-1);
                    $regex .= '(?:(?:\/{0,1}(?P<'.$parameter.'>[a-z0-9]*?)){0,1}\\/)';
                    $required = false;
                } else {
                    // Use custom parameter regex if it exists
                    $parameterRegex = '[a-z0-9]*?';

                    if(is_array($this->parametersRegex) && isset($this->parametersRegex[$parameter])) {
                        $parameterRegex = $this->parametersRegex[$parameter];
                    }

                    $regex .= '(?:\\/{0,1}(?P<' . $parameter . '>'. $parameterRegex .')\\/)';
                }
                $parameterNames[] = array('name' => $parameter, 'required' => $required);
                $parameter = '';
                $isParameter = false;

            } elseif($isParameter) {
                $parameter .= $character;
            } elseif($character === '/') {
                $regex .= '\\' . $character;
            } else {
                $regex .= $character;
            }

            $lastCharacter = $character;
        }

        $parameterValues = array();

        if(preg_match('/^'.$regex.'$/is', $url, $parameterValues)) {

            $parameters = array();

            if(count($parameterNames)) {
                foreach($parameterNames as $name) {
                    $parameterValue = (isset($parameterValues[$name['name']]) && !empty($parameterValues[$name['name']])) ? $parameterValues[$name['name']] : null;

                    if($name['required'] && $parameterValue === null) {
                        throw new RouterException('Missing required parameter ' . $name['name'], 404);
                    }

                    $parameters[$name['name']] = $parameterValue;
                }
            }

            $this->parameters = $parameters;
            return $this;
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
     * @return self
     */
    public function setUrl($url) {

        $parameters = array();
        $matches = array();

        if(preg_match_all('/'.self::PARAMETERS_REGEX_MATCH.'/is', $url, $matches)) {
            $parameters = $matches[1];
        }

        if(count($parameters)) {
            foreach($parameters as $param) {
                $this->parameters[$param] = '';
            }
        }

        $this->url = $url;
        return $this;
    }

    /**
     * Get alias for the url which can be used when getting the url route.
     * @return string
     */
    public function getAlias(){
        return $this->alias;
    }

    /**
     * Set the url alias for easier getting the url route.
     * @param string $alias
     * @return self
     */
    public function setAlias($alias){
        $this->alias = $alias;
        return $this;
    }

    public function setSettings($settings) {

        // Change as to alias
        if(isset($settings{'as'})) {
            $this->setAlias($settings['as']);
        }

        return parent::setSettings($settings);
    }

}
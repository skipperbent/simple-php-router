<?php
namespace Pecee\SimpleRouter;

abstract class LoadableRoute extends RouterEntry implements ILoadableRoute {

    const PARAMETERS_REGEX_MATCH = '{([A-Za-z\-\_]*?)\?{0,1}}';

    protected $url;
    protected $alias;

    public function getUrl() {
        return $this->url;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return static
     */
    public function setUrl($url) {

        $this->url = '/' . trim($url, '/') . '/';
        /*$this->originalUrl = $this->url;

        if(preg_match_all('/' . static::PARAMETERS_REGEX_MATCH . '/is', $this->url, $matches)) {
            $parameters = $matches[1];


            if (count($parameters)) {

                foreach (array_keys($parameters) as $key) {
                    $parameters[$key] = null;
                }

                $this->settings['parameters'] = $parameters;
            }

        }*/

        return $this;
    }

    /**
     * Get alias for the url which can be used when getting the url route.
     * @return string|array
     */
    public function getAlias(){
        return $this->alias;
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
                foreach ($this->getAlias() as $alias) {
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
        $this->alias = $alias;
        return $this;
    }

    public function setData(array $settings) {

        // Change as to alias
        if(isset($settings['as'])) {
            $this->setAlias($settings['as']);
        }

        return parent::setData($settings);
    }

}
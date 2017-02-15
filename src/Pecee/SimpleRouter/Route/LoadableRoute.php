<?php
namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\HttpException;

abstract class LoadableRoute extends Route implements ILoadableRoute
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $name;

    protected $regex;

    /**
     * Loads and renders middlewares-classes
     *
     * @param Request $request
     * @throws HttpException
     */
    public function loadMiddleware(Request $request)
    {
        $max = count($this->getMiddlewares());

        if ($max > 0) {

            for ($i = 0; $i < $max; $i++) {

                $middleware = $this->getMiddlewares()[$i];

                $middleware = $this->loadClass($middleware);

                if (($middleware instanceof IMiddleware) === false) {
                    throw new HttpException($middleware . ' must be instance of Middleware');
                }

                $middleware->handle($request);
            }
        }
    }

    public function matchRegex(Request $request, $url)
    {
        /* Match on custom defined regular expression */

        if ($this->regex === null) {
            return null;
        }

        $parameters = [];

        if (preg_match($this->regex, $request->getHost() . $url, $parameters) > 0) {

            /* Remove global match */
            $this->parameters = array_slice($parameters, 1);

            return true;
        }

        return false;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return static
     */
    public function setUrl($url)
    {
        $this->url = ($url === '/') ? '/' : '/' . trim($url, '/') . '/';

        if (strpos($this->url, $this->paramModifiers[0]) !== false) {

            $regex = sprintf(static::PARAMETERS_REGEX_MATCH, $this->paramModifiers[0], $this->paramOptionalSymbol, $this->paramModifiers[1]);

            if (preg_match_all('/' . $regex . '/is', $this->url, $matches)) {
                $this->parameters = array_fill_keys($matches[1], null);
            }
        }

        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Find url that matches method, parameters or name.
     * Used when calling the url() helper.
     *
     * @param string|null $method
     * @param array|null $parameters
     * @param string|null $name
     * @return string
     */
    public function findUrl($method = null, $parameters = null, $name = null)
    {
        $url = $this->getUrl();

        if ($this->getGroup() !== null && count($this->getGroup()->getDomains()) > 0) {
            $url = '//' . $this->getGroup()->getDomains()[0] . $url;
        }

        /* Contains parameters that aren't recognized and will be appended at the end of the url */
        $unknownParams = [];

        /* Create the param string - {parameter} */
        $param1 = $this->paramModifiers[0] . '%s' . $this->paramModifiers[1];

        /* Create the param string with the optional symbol - {parameter?} */
        $param2 = $this->paramModifiers[0] . '%s' . $this->paramOptionalSymbol . $this->paramModifiers[1];

        /* Replace any {parameter} in the url with the correct value */

        $params = $this->getParameters();
        $max = count($params) - 1;
        $keys = array_keys($params);

        for ($i = $max; $i >= 0; $i--) {
            $param = $keys[$i];
            $value = $value = ($parameters !== null && array_key_exists($param, $parameters)) ? $parameters[$param] : $params[$param];

            /* If parameter is specifically set to null - use the original-defined value */
            if ($value === null && isset($this->originalParameters[$param])) {
                $value = $this->originalParameters[$param];
            }

            if (stripos($url, $param1) !== false || stripos($url, $param) !== false) {
                /* Add parameter to the correct position */
                $url = str_ireplace([sprintf($param1, $param), sprintf($param2, $param)], $value, $url);
            } else {
                $unknownParams[$param] = $value;
            }
        }

        $url .= join('/', $unknownParams);



        return rtrim($url, '/') . '/';
    }

    /**
     * Returns the provided name for the router.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Check if route has given name.
     *
     * @param string $name
     * @return bool
     */
    public function hasName($name)
    {
        return (strtolower($this->name) === strtolower($name));
    }

    /**
     * Add regular expression match for the entire route.
     *
     * @param string $regex
     * @return static
     */
    public function setMatch($regex)
    {
        $this->regex = $regex;

        return $this;
    }

    /**
     * Get regular expression match used for matching route (if defined).
     *
     * @return string
     */
    public function getMatch()
    {
        return $this->regex;
    }

    /**
     * Sets the router name, which makes it easier to obtain the url or router at a later point.
     * Alias for LoadableRoute::setName().
     *
     * @see LoadableRoute::setName()
     * @param string|array $name
     * @return static
     */
    public function name($name)
    {
        return $this->setName($name);
    }

    /**
     * Sets the router name, which makes it easier to obtain the url or router at a later point.
     *
     * @param string $name
     * @return static $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
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
        if (isset($values['as'])) {
            if ($this->name !== null && $merge !== false) {
                $this->setName($values['as'] . '.' . $this->name);
            } else {
                $this->setName($values['as']);
            }
        }

        if (isset($values['prefix'])) {
            $this->setUrl($values['prefix'] . $this->getUrl());
        }

        parent::setSettings($values, $merge);

        return $this;
    }

}
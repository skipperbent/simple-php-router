<?php
namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\HttpException;

abstract class LoadableRoute extends Route implements ILoadableRoute
{
    const PARAMETERS_REGEX_MATCH = '%s([\w\-\_]*?)\%s{0,1}%s';

    /**
     * @var
     */
    protected $url;

    /**
     * @var string
     */
    protected $name;

    /**
     * Loads and renders middlewares-classes
     *
     * @param Request $request
     * @param ILoadableRoute $route
     * @throws HttpException
     */
    public function loadMiddleware(Request $request, ILoadableRoute $route)
    {
        if (count($this->getMiddlewares()) > 0) {

            $max = count($this->getMiddlewares());

            for ($i = 0; $i < $max; $i++) {

                $middleware = $this->getMiddlewares()[$i];

                $middleware = $this->loadClass($middleware);

                if (!($middleware instanceof IMiddleware)) {
                    throw new HttpException($middleware . ' must be instance of Middleware');
                }

                $middleware->handle($request, $route);
            }
        }
    }

    public function matchRegex(Request $request, $url) {

        /* Match on custom defined regular expression */

        if ($this->regex === null) {
            return null;
        }

        $parameters = [];

        if (preg_match($this->regex, $request->getHost() . $url, $parameters) !== false) {

            /* Remove global match */
            if (count($parameters) > 1) {
                $this->parameters = array_slice($parameters, 1);
            }

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
        $url = '';

        $parameters = (array)$parameters;

        if ($this->getGroup() !== null && count($this->getGroup()->getDomains()) > 0) {
            $url .= '//' . $this->getGroup()->getDomains()[0];
        }

        $url .= $this->getUrl();

        $params = array_merge($this->getParameters(), $parameters);

        /* Url that contains parameters that aren't recognized */
        $unknownParams = [];

        /* Create the param string - {} */
        $param1 = $this->paramModifiers[0] . '%s' . $this->paramModifiers[1];

        /* Create the param string with the optional symbol - {?} */
        $param2 = $this->paramModifiers[0] . '%s' . $this->paramOptionalSymbol . $this->paramModifiers[1];

        /* Let's parse the values of any {} parameter in the url */

        $max = count($params) - 1;
        $keys = array_keys($params);

        for ($i = $max; $i >= 0; $i--) {
            $param = $keys[$i];
            $value = $params[$param];

            $value = isset($parameters[$param]) ? $parameters[$param] : $value;

            if (stripos($url, $param1) !== false || stripos($url, $param) !== false) {
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
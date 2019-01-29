<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Router;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\SimpleRouter\Exceptions\HttpException;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

/**
 * Class LoadableRoute
 *
 * @package Pecee\SimpleRouter\Route
 */
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
     * @param Request $request
     * @param Router $router
     * @throws HttpException
     * @throws NotFoundHttpException
     */
    public function loadMiddleware(Request $request, Router $router): void
    {
        $router->debug('Loading middlewares');

        foreach ($this->getMiddlewares() as $middleware) {

            if (\is_object($middleware) === false) {
                $middleware = $router->getClassLoader()->loadClass($middleware);
            }

            if (($middleware instanceof IMiddleware) === false) {
                throw new HttpException($middleware . ' must be inherit the IMiddleware interface');
            }

            $className = \get_class($middleware);

            $router->debug('Loading middleware "%s"', $className);
            $middleware->handle($request);
            $router->debug('Finished loading middleware "%s"', $className);
        }

        $router->debug('Finished loading middlewares');
    }

    /**
     * @param Request $request
     * @param $url
     * @return bool|null
     */
    public function matchRegex(Request $request, $url): ?bool
    {
        /* Match on custom defined regular expression */
        if ($this->regex === null) {
            return null;
        }

        return ((bool)preg_match($this->regex, $request->getHost() . $url) !== false);
    }

    /**
     * @param string $url
     * @return static
     */
    public function setUrl(string $url): ILoadableRoute
    {
        $this->url = ($url === '/') ? '/' : '/' . trim($url, '/') . '/';

        if (strpos($this->url, $this->paramModifiers[0]) !== false) {

            $regex = sprintf(static::PARAMETERS_REGEX_FORMAT, $this->paramModifiers[0], $this->paramOptionalSymbol, $this->paramModifiers[1]);

            if ((bool)preg_match_all('/' . $regex . '/u', $this->url, $matches) !== false) {
                $this->parameters = array_fill_keys($matches[1], null);
            }
        }

        return $this;
    }

    /**
     * @param string $url
     * @return ILoadableRoute
     */
    public function prependUrl(string $url): ILoadableRoute
    {
        return $this->setUrl(rtrim($url, '/') . $this->url);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param null|string $method
     * @param null $parameters
     * @param null|string $name
     * @return string
     */
    public function findUrl(?string $method = null, $parameters = null, ?string $name = null): string
    {
        $url = $this->getUrl();

        $group = $this->getGroup();

        if ($group !== null && \count($group->getDomains()) !== 0) {
            $url = '//' . $group->getDomains()[0] . $url;
        }

        /* Create the param string - {parameter} */
        $param1 = $this->paramModifiers[0] . '%s' . $this->paramModifiers[1];
        /* Create the param string with the optional symbol - {parameter?} */
        $param2 = $this->paramModifiers[0] . '%s' . $this->paramOptionalSymbol . $this->paramModifiers[1];
        /* Replace any {parameter} in the url with the correct value */
        $params = $this->getParameters();

        foreach (array_keys($params) as $param) {

            if ($parameters === '' || (\is_array($parameters) === true && \count($parameters) === 0)) {
                $value = '';
            } else {
                $p = (array)$parameters;
                $value = array_key_exists($param, $p) ? $p[$param] : $params[$param];

                /* If parameter is specifically set to null - use the original-defined value */
                if ($value === null && isset($this->originalParameters[$param]) === true) {
                    $value = $this->originalParameters[$param];
                }
            }

            if (stripos($url, $param1) !== false || stripos($url, $param) !== false) {
                /* Add parameter to the correct position */
                $url = str_ireplace([sprintf($param1, $param), sprintf($param2, $param)], $value, $url);
            } else {
                /* Parameter aren't recognized and will be appended at the end of the url */
                $url .= $value . '/';
            }
        }

        return rtrim('/' . ltrim($url, '/'), '/') . '/';
    }

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasName(string $name): bool
    {
        return strtolower($this->name) === strtolower($name);
    }

    /**
     * @param $regex
     * @return static
     */
    public function setMatch($regex): ILoadableRoute
    {
        $this->regex = $regex;

        return $this;
    }

    /**
     * @return string
     */
    public function getMatch(): string
    {
        return $this->regex;
    }

    /**
     * @param $name
     * @return ILoadableRoute
     */
    public function name($name): ILoadableRoute
    {
        return $this->setName($name);
    }

    /**
     * @param string $name
     * @return static
     */
    public function setName(string $name): ILoadableRoute
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param array $values
     * @param bool $merge
     * @return static
     */
    public function setSettings(array $values, bool $merge = false): IRoute
    {
        if (isset($values['as']) === true) {

            $name = $values['as'];

            if ($this->name !== null && $merge !== false) {
                $name .= '.' . $this->name;
            }

            $this->setName($name);
        }

        if (isset($values['prefix']) === true) {
            $this->prependUrl($values['prefix']);
        }

        return parent::setSettings($values, $merge);
    }
}
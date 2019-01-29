<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Router;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

abstract class Route implements IRoute
{
    protected const PARAMETERS_REGEX_FORMAT = '%s([\w]+)(\%s?)%s';
    protected const PARAMETERS_DEFAULT_REGEX = '[\w]+';

    public const REQUEST_TYPE_GET = 'get';
    public const REQUEST_TYPE_POST = 'post';
    public const REQUEST_TYPE_PUT = 'put';
    public const REQUEST_TYPE_PATCH = 'patch';
    public const REQUEST_TYPE_OPTIONS = 'options';
    public const REQUEST_TYPE_DELETE = 'delete';
    public const REQUEST_TYPE_HEAD = 'head';

    public static $requestTypes = [
        self::REQUEST_TYPE_GET,
        self::REQUEST_TYPE_POST,
        self::REQUEST_TYPE_PUT,
        self::REQUEST_TYPE_PATCH,
        self::REQUEST_TYPE_OPTIONS,
        self::REQUEST_TYPE_DELETE,
        self::REQUEST_TYPE_HEAD,
    ];

    /**
     * @var bool
     */
    protected $filterEmptyParams = true;

    /**
     * @var string|null
     */
    protected $defaultParameterRegex;
    protected $paramModifiers = '{}';
    protected $paramOptionalSymbol = '?';
    protected $urlRegex = '/^%s\/?$/u';
    protected $group;
    protected $parent;
    protected $callback;
    protected $defaultNamespace;

    /* Default options */
    protected $namespace;
    protected $requestMethods = [];
    protected $where = [];
    protected $parameters = [];
    protected $originalParameters = [];
    protected $middlewares = [];

    /**
     * @param Request $request
     * @param Router $router
     * @return null|string
     * @throws NotFoundHttpException
     */
    public function renderRoute(Request $request, Router $router): ?string
    {
        $router->debug('Starting rendering route "%s"', \get_class($this));

        $callback = $this->getCallback();

        if ($callback === null) {
            return null;
        }

        $router->debug('Parsing parameters');
        $parameters = $this->getParameters();
        $router->debug('Finished parsing parameters');

        /* Filter parameters with null-value */
        if ($this->filterEmptyParams === true) {
            $parameters = array_filter($parameters, function ($var) {
                return ($var !== null);
            });
        }

        /* Render callback function */
        if (\is_callable($callback) === true) {
            $router->debug('Executing callback');
            /* When the callback is a function */

            return $router->getClassLoader()->loadClosure($callback, $parameters);
        }

        /* When the callback is a class + method */
        $controller = explode('@', $callback);
        $namespace = $this->getNamespace();
        $className = ($namespace !== null && $controller[0][0] !== '\\') ? $namespace . '\\' . $controller[0] : $controller[0];
        $router->debug('Loading class %s', $className);
        $class = $router->getClassLoader()->loadClass($className);

        if (\count($controller) === 1) {
            $controller[1] = '__invoke';
        }

        $method = $controller[1];

        if (method_exists($class, $method) === false) {
            throw new NotFoundHttpException(sprintf('Method "%s" does not exist in class "%s"', $method, $className), 404);
        }

        $router->debug('Executing callback');

        return \call_user_func_array([$class, $method], $parameters);
    }

    /**
     * @param $route
     * @param $url
     * @param null $parameterRegex
     * @return array|null
     */
    protected function parseParameters($route, $url, $parameterRegex = null)
    {
        $regex = (strpos($route, $this->paramModifiers[0]) === false) ? null :
            sprintf
            (
                static::PARAMETERS_REGEX_FORMAT,
                $this->paramModifiers[0],
                $this->paramOptionalSymbol,
                $this->paramModifiers[1]
            );

        // Ensures that host names/domains will work with parameters
        $url = '/' . ltrim($url, '/');
        $urlRegex = '';
        $parameters = [];

        if ($regex === null || (bool)preg_match_all('/' . $regex . '/u', $route, $parameters) === false) {
            $urlRegex = preg_quote($route, '/');
        } else {
            foreach (preg_split('/((\-?\/?)\{[^}]+\})/', $route) as $key => $t) {
                $regex = '';
                if ($key < \count($parameters[1])) {

                    $name = $parameters[1][$key];

                    /* If custom regex is defined, use that */
                    if (isset($this->where[$name]) === true) {
                        $regex = $this->where[$name];
                    } else if ($parameterRegex !== null) {
                        $regex = $parameterRegex;
                    } else {
                        $regex = $this->defaultParameterRegex ?? static::PARAMETERS_DEFAULT_REGEX;
                    }
                    $regex = sprintf('((\/|\-)(?P<%2$s>%3$s))%1$s', $parameters[2][$key], $name, $regex);
                }
                $urlRegex .= preg_quote($t, '/') . $regex;
            }
        }

        if (trim($urlRegex) === '' || (bool)preg_match(sprintf($this->urlRegex, $urlRegex), $url, $matches) === false) {
            return null;
        }

        $values = [];

        if (isset($parameters[1]) === true) {

            /* Only take matched parameters with name */
            foreach ((array)$parameters[1] as $name) {
                $values[$name] = (isset($matches[$name]) === true && $matches[$name] !== '') ? $matches[$name] : null;
            }
        }

        return $values;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        if (\is_string($this->callback) === true && strpos($this->callback, '@') !== false) {
            return $this->callback;
        }

        return 'function:' . md5($this->callback);
    }

    /**
     * @param array $methods
     * @return static
     */
    public function setRequestMethods(array $methods): IRoute
    {
        $this->requestMethods = $methods;

        return $this;
    }

    /**
     * @return array
     */
    public function getRequestMethods(): array
    {
        return $this->requestMethods;
    }

    /**
     * @return null|IRoute
     */
    public function getParent(): ?IRoute
    {
        return $this->parent;
    }

    /**
     * @return null|IGroupRoute
     */
    public function getGroup(): ?IGroupRoute
    {
        return $this->group;
    }

    /**
     * @param IGroupRoute $group
     * @return static
     */
    public function setGroup(IGroupRoute $group): IRoute
    {
        $this->group = $group;
        /* Add/merge parent settings with child */

        return $this->setSettings($group->toArray(), true);
    }

    /**
     * @param IRoute $parent
     * @return static
     */
    public function setParent(IRoute $parent): IRoute
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @param $callback
     * @return static
     */
    public function setCallback($callback): IRoute
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @return callable|string
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @return null|string
     */
    public function getMethod(): ?string
    {
        if (\is_string($this->callback) === true && strpos($this->callback, '@') !== false) {
            $tmp = explode('@', $this->callback);

            return $tmp[1];
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function getClass(): ?string
    {
        if (\is_string($this->callback) === true && strpos($this->callback, '@') !== false) {
            $tmp = explode('@', $this->callback);

            return $tmp[0];
        }

        return null;
    }

    /**
     * @param string $method
     * @return static
     */
    public function setMethod(string $method): IRoute
    {
        $this->callback = sprintf('%s@%s', $this->getClass(), $method);

        return $this;
    }

    /**
     * @param string $class
     * @return static
     */
    public function setClass(string $class): IRoute
    {
        $this->callback = sprintf('%s@%s', $class, $this->getMethod());

        return $this;
    }

    /**
     * @param string $namespace
     * @return static
     */
    public function setNamespace(string $namespace): IRoute
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @param $namespace
     * @return static
     */
    public function setDefaultNamespace($namespace): IRoute
    {
        $this->defaultNamespace = $namespace;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getDefaultNamespace(): ?string
    {
        return $this->defaultNamespace;
    }

    /**
     * @return null|string
     */
    public function getNamespace(): ?string
    {
        return $this->namespace ?? $this->defaultNamespace;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $values = [];

        if ($this->namespace !== null) {
            $values['namespace'] = $this->namespace;
        }

        if (\count($this->requestMethods) !== 0) {
            $values['method'] = $this->requestMethods;
        }

        if (\count($this->where) !== 0) {
            $values['where'] = $this->where;
        }

        if (\count($this->middlewares) !== 0) {
            $values['middleware'] = $this->middlewares;
        }

        if ($this->defaultParameterRegex !== null) {
            $values['defaultParameterRegex'] = $this->defaultParameterRegex;
        }

        return $values;
    }

    /**
     * @param array $values
     * @param bool $merge
     * @return static
     */
    public function setSettings(array $values, bool $merge = false): IRoute
    {
        if ($this->namespace === null && isset($values['namespace']) === true) {
            $this->setNamespace($values['namespace']);
        }

        if (isset($values['method']) === true) {
            $this->setRequestMethods(array_merge($this->requestMethods, (array)$values['method']));
        }

        if (isset($values['where']) === true) {
            $this->setWhere(array_merge($this->where, (array)$values['where']));
        }

        if (isset($values['parameters']) === true) {
            $this->setParameters(array_merge($this->parameters, (array)$values['parameters']));
        }

        // Push middleware if multiple
        if (isset($values['middleware']) === true) {
            $this->setMiddlewares(array_merge((array)$values['middleware'], $this->middlewares));
        }

        if (isset($values['defaultParameterRegex']) === true) {
            $this->setDefaultParameterRegex($values['defaultParameterRegex']);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * @param array $options
     * @return static
     */
    public function setWhere(array $options): IRoute
    {
        $this->where = $options;

        return $this;
    }

    /**
     * @param array $options
     * @return IRoute
     */
    public function where(array $options)
    {
        return $this->setWhere($options);
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        /* Sort the parameters after the user-defined param order, if any */
        $parameters = [];

        if (\count($this->originalParameters) !== 0) {
            $parameters = $this->originalParameters;
        }

        return array_merge($parameters, $this->parameters);
    }

    /**
     * @param array $parameters
     * @return static
     */
    public function setParameters(array $parameters): IRoute
    {
        /*
         * If this is the first time setting parameters we store them so we
         * later can organize the array, in case somebody tried to sort the array.
         */
        if (\count($parameters) !== 0 && \count($this->originalParameters) === 0) {
            $this->originalParameters = $parameters;
        }

        $this->parameters = array_merge($this->parameters, $parameters);

        return $this;
    }

    /**
     * @param $middleware
     * @return static
     */
    public function setMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @param $middleware
     * @return static
     */
    public function addMiddleware($middleware): IRoute
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @param array $middlewares
     * @return static
     */
    public function setMiddlewares(array $middlewares): IRoute
    {
        $this->middlewares = $middlewares;

        return $this;
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * @param $regex
     * @return static
     */
    public function setDefaultParameterRegex($regex)
    {
        $this->defaultParameterRegex = $regex;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultParameterRegex(): string
    {
        return $this->defaultParameterRegex;
    }
}
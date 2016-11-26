<?php
namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

class RouteResource extends LoadableRoute implements IControllerRoute
{
    protected $urls = [
        'index'   => '',
        'create'  => 'create',
        'store'   => '',
        'show'    => '',
        'edit'    => 'edit',
        'update'  => '',
        'destroy' => '',
    ];

    protected $methodNames = [
        'index'   => 'index',
        'create'  => 'create',
        'store'   => 'store',
        'show'    => 'show',
        'edit'    => 'edit',
        'update'  => 'update',
        'destroy' => 'destroy',
    ];

    protected $names = [];
    protected $controller;

    public function __construct($url, $controller)
    {
        $this->setUrl($url);
        $this->controller = $controller;
        $this->setName(trim(str_replace('/', '.', $url), '/'));
    }

    /**
     * Check if route has given name.
     *
     * @param string $name
     * @return bool
     */
    public function hasName($name)
    {
        if ($this->name === null) {
            return false;
        }

        if (strtolower($this->name) === strtolower($name)) {
            return true;
        }

        /* Remove method/type */
        if (strpos($name, '.') !== false) {
            $name = substr($name, 0, strrpos($name, '.'));
        }

        return (strtolower($this->name) === strtolower($name));
    }

    public function findUrl($method = null, $parameters = null, $name = null)
    {
        $method = array_search($name, $this->names, false);
        if ($method !== false) {
            return rtrim($this->url . $this->urls[$method], '/') . '/';
        }

        return $this->url;
    }

    public function renderRoute(Request $request)
    {
        if ($this->getCallback() !== null && is_callable($this->getCallback())) {
            // When the callback is a function
            call_user_func_array($this->getCallback(), $this->getParameters());
        } else {
            // When the callback is a method
            $controller = explode('@', $this->getCallback());
            $className = $this->getNamespace() . '\\' . $controller[0];
            $class = $this->loadClass($className);
            $method = strtolower($controller[1]);

            if (!method_exists($class, $method)) {
                throw new NotFoundHttpException(sprintf('Method %s does not exist in class %s', $method, $className), 404);
            }

            call_user_func_array([$class, $method], $this->getParameters());

            return $class;
        }

        return null;
    }

    protected function call($method, $parameters)
    {
        $this->setCallback($this->controller . '@' . $method);
        $this->parameters = $parameters;

        return true;
    }

    public function matchRoute(Request $request)
    {
        $url = parse_url(urldecode($request->getUri()), PHP_URL_PATH);
        $url = rtrim($url, '/') . '/';

        $route = rtrim($this->url, '/') . '/{id?}/{action?}';

        $parameters = $this->parseParameters($route, $url);

        if ($parameters !== null) {

            $parameters = array_merge($this->parameters, (array)$parameters);

            $action = isset($parameters['action']) ? $parameters['action'] : null;
            unset($parameters['action']);

            $method = $request->getMethod();

            // Delete
            if ($method === static::REQUEST_TYPE_DELETE && isset($parameters['id'])) {
                return $this->call($this->methodNames['destroy'], $parameters);
            }

            // Update
            if (isset($parameters['id']) && in_array($method, [static::REQUEST_TYPE_PATCH, static::REQUEST_TYPE_PUT])) {
                return $this->call($this->methodNames['update'], $parameters);
            }

            // Edit
            if ($method === static::REQUEST_TYPE_GET && isset($parameters['id']) && strtolower($action) === 'edit') {
                return $this->call($this->methodNames['edit'], $parameters);
            }

            // Create
            if ($method === static::REQUEST_TYPE_GET && strtolower($action) === 'create') {
                return $this->call($this->methodNames['create'], $parameters);
            }

            // Save
            if ($method === static::REQUEST_TYPE_POST) {
                return $this->call($this->methodNames['store'], $parameters);
            }

            // Show
            if ($method === static::REQUEST_TYPE_GET && isset($parameters['id'])) {
                return $this->call($this->methodNames['show'], $parameters);
            }

            // Index
            return $this->call($this->methodNames['index'], $parameters);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param string $controller
     * @return static
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;

        $this->names = [
            'index'   => $this->name . '.index',
            'create'  => $this->name . '.create',
            'store'   => $this->name . '.store',
            'show'    => $this->name . '.show',
            'edit'    => $this->name . '.edit',
            'update'  => $this->name . '.update',
            'destroy' => $this->name . '.destroy',
        ];

        return $this;
    }

    /**
     * Define custom method name for resource controller
     *
     * @param array $names
     * @return static $this
     */
    public function setMethodNames(array $names)
    {
        $this->methodNames = $names;

        return $this;
    }

    /**
     * Get method names
     *
     * @return array $this
     */
    public function getMethodNames()
    {
        return $this->methodNames;
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
        if (isset($values['names'])) {
            $this->names = $values['names'];
        }

        if (isset($values['methods'])) {
            $this->methodNames = $values['methods'];
        }

        parent::setSettings($values, $merge);

        return $this;
    }

}
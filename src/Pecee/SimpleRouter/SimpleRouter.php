<?php
/**
 * ---------------------------
 * Router helper class
 * ---------------------------
 *
 * This class is added so calls can be made statically like Router::get() making the code look pretty.
 * It also adds some extra functionality like default-namespace.
 */

namespace Pecee\SimpleRouter;

use DI\Container;
use Pecee\Http\Url;
use Pecee\Http\Request;
use Pecee\Http\Response;
use Pecee\SimpleRouter\Route\IRoute;
use Pecee\SimpleRouter\Route\RouteUrl;
use Pecee\SimpleRouter\Route\RouteGroup;
use Pecee\SimpleRouter\Route\IGroupRoute;
use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\SimpleRouter\Route\RouteResource;
use Pecee\SimpleRouter\Route\RouteController;
use Pecee\SimpleRouter\Handlers\IEventHandler;
use Pecee\Exceptions\InvalidArgumentException;
use Pecee\SimpleRouter\Route\RoutePartialGroup;
use Pecee\SimpleRouter\Route\IPartialGroupRoute;
use Pecee\SimpleRouter\ClassLoader\IClassLoader;
use Pecee\Http\Exceptions\MalformedUrlException;
use Pecee\SimpleRouter\Exceptions\HttpException;
use Pecee\SimpleRouter\Handlers\CallbackExceptionHandler;

/**
 * Class SimpleRouter
 *
 * @package Pecee\SimpleRouter
 */
class SimpleRouter
{
    /**
     * @var string|null
     */
    protected static $defaultNamespace;

    /**
     * @var Response
     */
    protected static $response;

    /**
     * @var Router
     */
    protected static $router;

    /**
     * @throws Exceptions\NotFoundHttpException
     * @throws HttpException
     * @throws \Pecee\Http\Middleware\Exceptions\TokenMismatchException
     */
    public static function start(): void
    {
        echo static::router()->start();
    }

    /**
     * @return array
     */
    public static function startDebug(): array
    {
        $routerOutput = null;

        try {
            ob_start();
            static::router()->setDebugEnabled(true)->start();
            $routerOutput = ob_get_contents();
            ob_end_clean();
        } catch (\Exception $e) {

        }

        // Try to parse library version
        $composerFile = \dirname(__DIR__, 3) . '/composer.lock';
        $version = false;

        if (is_file($composerFile) === true) {
            $composerInfo = json_decode(file_get_contents($composerFile), true);

            if (isset($composerInfo['packages']) === true && \is_array($composerInfo['packages']) === true) {
                foreach ($composerInfo['packages'] as $package) {
                    if (isset($package['name']) === true && strtolower($package['name']) === 'pecee/simple-router') {
                        $version = $package['version'];
                        break;
                    }
                }
            }
        }

        $request = static::request();
        $router = static::router();

        return [
            'url' => $request->getUrl(),
            'method' => $request->getMethod(),
            'host' => $request->getHost(),
            'loaded_routes' => $request->getLoadedRoutes(),
            'all_routes' => $router->getRoutes(),
            'boot_managers' => $router->getBootManagers(),
            'csrf_verifier' => $router->getCsrfVerifier(),
            'log' => $router->getDebugLog(),
            'event_handlers' => $router->getEventHandlers(),
            'router_output' => $routerOutput,
            'library_version' => $version,
            'php_version' => PHP_VERSION,
            'server_params' => $request->getHeaders(),
        ];
    }

    /**
     * @param string $defaultNamespace
     */
    public static function setDefaultNamespace(string $defaultNamespace): void
    {
        static::$defaultNamespace = $defaultNamespace;
    }

    /**
     * @param BaseCsrfVerifier $baseCsrfVerifier
     */
    public static function csrfVerifier(BaseCsrfVerifier $baseCsrfVerifier): void
    {
        static::router()->setCsrfVerifier($baseCsrfVerifier);
    }

    /**
     * @param IEventHandler $eventHandler
     */
    public static function addEventHandler(IEventHandler $eventHandler): void
    {
        static::router()->addEventHandler($eventHandler);
    }

    /**
     * @param IRouterBootManager $bootManager
     */
    public static function addBootManager(IRouterBootManager $bootManager): void
    {
        static::router()->addBootManager($bootManager);
    }

    /**
     * @param $where
     * @param $to
     * @param int $httpCode
     * @return IRoute
     */
    public static function redirect($where, $to, $httpCode = 301): IRoute
    {
        return static::get($where, function () use ($to, $httpCode) {
            static::response()->redirect($to, $httpCode);
        });
    }

    /**
     * @param string $url
     * @param $callback
     * @param array|null $settings
     * @return IRoute
     */
    public static function get(string $url, $callback, array $settings = null): IRoute
    {
        return static::match(['get'], $url, $callback, $settings);
    }

    /**
     * @param string $url
     * @param $callback
     * @param array|null $settings
     * @return IRoute
     */
    public static function post(string $url, $callback, array $settings = null): IRoute
    {
        return static::match(['post'], $url, $callback, $settings);
    }

    /**
     * @param string $url
     * @param $callback
     * @param array|null $settings
     * @return IRoute
     */
    public static function put(string $url, $callback, array $settings = null): IRoute
    {
        return static::match(['put'], $url, $callback, $settings);
    }

    /**
     * @param string $url
     * @param $callback
     * @param array|null $settings
     * @return IRoute
     */
    public static function patch(string $url, $callback, array $settings = null): IRoute
    {
        return static::match(['patch'], $url, $callback, $settings);
    }

    /**
     * @param string $url
     * @param $callback
     * @param array|null $settings
     * @return IRoute
     */
    public static function options(string $url, $callback, array $settings = null): IRoute
    {
        return static::match(['options'], $url, $callback, $settings);
    }

    /**
     * @param string $url
     * @param $callback
     * @param array|null $settings
     * @return IRoute
     */
    public static function delete(string $url, $callback, array $settings = null): IRoute
    {
        return static::match(['delete'], $url, $callback, $settings);
    }

    /**
     * @param array $settings
     * @param \Closure $callback
     * @return IGroupRoute
     */
    public static function group(array $settings, \Closure $callback): IGroupRoute
    {
        if (\is_callable($callback) === false) {
            throw new InvalidArgumentException('Invalid callback provided. Only functions or methods supported');
        }

        $group = new RouteGroup();
        $group->setCallback($callback);
        $group->setSettings($settings);

        static::router()->addRoute($group);

        return $group;
    }

    /**
     * @param string $url
     * @param \Closure $callback
     * @param array $settings
     * @return IPartialGroupRoute
     */
    public static function partialGroup(string $url, \Closure $callback, array $settings = []): IPartialGroupRoute
    {
        if (\is_callable($callback) === false) {
            throw new InvalidArgumentException('Invalid callback provided. Only functions or methods supported');
        }

        $settings['prefix'] = $url;

        $group = new RoutePartialGroup();
        $group->setSettings($settings);
        $group->setCallback($callback);

        static::router()->addRoute($group);

        return $group;
    }

    /**
     * @param string $url
     * @param $callback
     * @param array|null $settings
     * @return IRoute
     */
    public static function basic(string $url, $callback, array $settings = null): IRoute
    {
        return static::match(['get', 'post'], $url, $callback, $settings);
    }

    /**
     * @param string $url
     * @param $callback
     * @param array|null $settings
     * @return IRoute
     */
    public static function form(string $url, $callback, array $settings = null): IRoute
    {
        return static::match(['get', 'post'], $url, $callback, $settings);
    }

    /**
     * @param array $requestMethods
     * @param string $url
     * @param $callback
     * @param array|null $settings
     * @return IRoute
     */
    public static function match(array $requestMethods, string $url, $callback, array $settings = null)
    {
        $route = new RouteUrl($url, $callback);
        $route->setRequestMethods($requestMethods);
        $route = static::addDefaultNamespace($route);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        return static::router()->addRoute($route);
    }

    /**
     * @param string $url
     * @param $callback
     * @param array|null $settings
     * @return IRoute
     */
    public static function all(string $url, $callback, array $settings = null)
    {
        $route = new RouteUrl($url, $callback);
        $route = static::addDefaultNamespace($route);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        return static::router()->addRoute($route);
    }

    /**
     * @param string $url
     * @param $controller
     * @param array|null $settings
     * @return IRoute
     */
    public static function controller(string $url, $controller, array $settings = null)
    {
        $route = new RouteController($url, $controller);
        $route = static::addDefaultNamespace($route);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        return static::router()->addRoute($route);
    }

    /**
     * @param string $url
     * @param $controller
     * @param array|null $settings
     * @return IRoute
     */
    public static function resource(string $url, $controller, array $settings = null)
    {
        $route = new RouteResource($url, $controller);
        $route = static::addDefaultNamespace($route);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        return static::router()->addRoute($route);
    }

    /**
     * @param \Closure $callback
     * @return CallbackExceptionHandler
     */
    public static function error(\Closure $callback): CallbackExceptionHandler
    {
        $routes = static::router()->getRoutes();

        $callbackHandler = new CallbackExceptionHandler($callback);

        $group = new RouteGroup();
        $group->addExceptionHandler($callbackHandler);

        array_unshift($routes, $group);

        static::router()->setRoutes($routes);

        return $callbackHandler;
    }

    /**
     * @param null|string $name
     * @param null $parameters
     * @param array|null $getParams
     * @return Url
     */
    public static function getUrl(?string $name = null, $parameters = null, ?array $getParams = null): Url
    {
        try {
            return static::router()->getUrl($name, $parameters, $getParams);
        } catch (\Exception $e) {
            try {
                return new Url('/');
            } catch (MalformedUrlException $e) {

            }
        }

        // This will never happen...
        return null;
    }

    /**
     * @return Request
     */
    public static function request(): Request
    {
        return static::router()->getRequest();
    }

    /**
     * @return Response
     */
    public static function response(): Response
    {
        if (static::$response === null) {
            static::$response = new Response(static::request());
        }

        return static::$response;
    }

    /**
     * @return Router
     */
    public static function router(): Router
    {
        if (static::$router === null) {
            static::$router = new Router();
        }

        return static::$router;
    }

    /**
     * @param IRoute $route
     * @return IRoute
     */
    public static function addDefaultNamespace(IRoute $route): IRoute
    {
        if (static::$defaultNamespace !== null) {

            $callback = $route->getCallback();

            /* Only add default namespace on relative callbacks */
            if ($callback === null || (\is_string($callback) === true && $callback[0] !== '\\')) {

                $namespace = static::$defaultNamespace;

                $currentNamespace = $route->getNamespace();

                if ($currentNamespace !== null) {
                    $namespace .= '\\' . $currentNamespace;
                }

                $route->setDefaultNamespace($namespace);

            }
        }

        return $route;
    }

    /**
     * @param Container $container
     * @return IClassLoader
     */
    public static function enableDependencyInjection(Container $container): IClassLoader
    {
        return static::router()
            ->getClassLoader()
            ->useDependencyInjection(true)
            ->setContainer($container);
    }

    /**
     * @return null|string
     */
    public static function getDefaultNamespace(): ?string
    {
        return static::$defaultNamespace;
    }
}
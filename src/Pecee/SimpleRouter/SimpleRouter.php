<?php
/**
 * ---------------------------
 * Router helper class
 * ---------------------------
 *
 * This class is added so calls can be made statically like SimpleRouter::get() making the code look pretty.
 * It also adds some extra functionality like default-namespace etc.
 */

namespace Pecee\SimpleRouter;

use Closure;
use Exception;
use Pecee\Exceptions\InvalidArgumentException;
use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\Http\Request;
use Pecee\Http\Response;
use Pecee\Http\Url;
use Pecee\SimpleRouter\ClassLoader\IClassLoader;
use Pecee\SimpleRouter\Exceptions\HttpException;
use Pecee\SimpleRouter\Handlers\CallbackExceptionHandler;
use Pecee\SimpleRouter\Handlers\IEventHandler;
use Pecee\SimpleRouter\Route\IGroupRoute;
use Pecee\SimpleRouter\Route\ILoadableRoute;
use Pecee\SimpleRouter\Route\IPartialGroupRoute;
use Pecee\SimpleRouter\Route\IRoute;
use Pecee\SimpleRouter\Route\RouteController;
use Pecee\SimpleRouter\Route\RouteGroup;
use Pecee\SimpleRouter\Route\RoutePartialGroup;
use Pecee\SimpleRouter\Route\RouteResource;
use Pecee\SimpleRouter\Route\RouteUrl;

class SimpleRouter
{
    /**
     * Default namespace added to all routes
     * @var string|null
     */
    protected static $defaultNamespace;

    /**
     * The response object
     * @var Response
     */
    protected static $response;

    /**
     * Router instance
     * @var Router
     */
    protected static $router;

    /**
     * Start routing
     *
     * @throws \Pecee\SimpleRouter\Exceptions\NotFoundHttpException
     * @throws \Pecee\Http\Middleware\Exceptions\TokenMismatchException
     * @throws HttpException
     * @throws Exception
     */
    public static function start(): void
    {
        // Set default namespaces
        foreach (static::router()->getRoutes() as $route) {
            static::addDefaultNamespace($route);
        }

        echo static::router()->start();
    }

    /**
     * Start the routing an return array with debugging-information
     *
     * @return array
     */
    public static function startDebug(): array
    {
        $routerOutput = null;

        try {
            ob_start();
            static::router()->setDebugEnabled(true)->start();
            $routerOutput = ob_get_clean();
        } catch (Exception $e) {

        }

        // Try to parse library version
        $composerFile = dirname(__DIR__, 3) . '/composer.lock';
        $version = false;

        if (is_file($composerFile) === true) {
            $composerInfo = json_decode(file_get_contents($composerFile), true);

            if (isset($composerInfo['packages']) === true && is_array($composerInfo['packages']) === true) {
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
            'url'             => $request->getUrl(),
            'method'          => $request->getMethod(),
            'host'            => $request->getHost(),
            'loaded_routes'   => $request->getLoadedRoutes(),
            'all_routes'      => $router->getRoutes(),
            'boot_managers'   => $router->getBootManagers(),
            'csrf_verifier'   => $router->getCsrfVerifier(),
            'log'             => $router->getDebugLog(),
            'event_handlers'  => $router->getEventHandlers(),
            'router_output'   => $routerOutput,
            'library_version' => $version,
            'php_version'     => PHP_VERSION,
            'server_params'   => $request->getHeaders(),
        ];
    }

    /**
     * Set default namespace which will be prepended to all routes.
     *
     * @param string $defaultNamespace
     */
    public static function setDefaultNamespace(string $defaultNamespace): void
    {
        static::$defaultNamespace = $defaultNamespace;
    }

    /**
     * Base CSRF verifier
     *
     * @param BaseCsrfVerifier $baseCsrfVerifier
     */
    public static function csrfVerifier(BaseCsrfVerifier $baseCsrfVerifier): void
    {
        static::router()->setCsrfVerifier($baseCsrfVerifier);
    }

    /**
     * Add new event handler to the router
     *
     * @param IEventHandler $eventHandler
     */
    public static function addEventHandler(IEventHandler $eventHandler): void
    {
        static::router()->addEventHandler($eventHandler);
    }

    /**
     * Boot managers allows you to alter the routes before the routing occurs.
     * Perfect if you want to load pretty-urls from a file or database.
     *
     * @param IRouterBootManager $bootManager
     */
    public static function addBootManager(IRouterBootManager $bootManager): void
    {
        static::router()->addBootManager($bootManager);
    }

    /**
     * Redirect to when route matches.
     *
     * @param string $where
     * @param string $to
     * @param int $httpCode
     * @return IRoute
     */
    public static function redirect(string $where, string $to, int $httpCode = 301): IRoute
    {
        return static::get($where, static function () use ($to, $httpCode): void {
            static::response()->redirect($to, $httpCode);
        });
    }

    /**
     * Route the given url to your callback on GET request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     *
     * @return RouteUrl|IRoute
     */
    public static function get(string $url, $callback, array $settings = null): IRoute
    {
        return static::match([Request::REQUEST_TYPE_GET], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on POST request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public static function post(string $url, $callback, array $settings = null): IRoute
    {
        return static::match([Request::REQUEST_TYPE_POST], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on PUT request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public static function put(string $url, $callback, array $settings = null): IRoute
    {
        return static::match([Request::REQUEST_TYPE_PUT], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on PATCH request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public static function patch(string $url, $callback, array $settings = null): IRoute
    {
        return static::match([Request::REQUEST_TYPE_PATCH], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on OPTIONS request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public static function options(string $url, $callback, array $settings = null): IRoute
    {
        return static::match([Request::REQUEST_TYPE_OPTIONS], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on DELETE request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public static function delete(string $url, $callback, array $settings = null): IRoute
    {
        return static::match([Request::REQUEST_TYPE_DELETE], $url, $callback, $settings);
    }

    /**
     * Groups allows for encapsulating routes with special settings.
     *
     * @param array $settings
     * @param Closure $callback
     * @return RouteGroup|IGroupRoute
     * @throws InvalidArgumentException
     */
    public static function group(array $settings, Closure $callback): IGroupRoute
    {
        $group = new RouteGroup();
        $group->setCallback($callback);
        $group->setSettings($settings);

        static::router()->addRoute($group);

        return $group;
    }

    /**
     * Special group that has the same benefits as group but supports
     * parameters and which are only rendered when the url matches.
     *
     * @param string $url
     * @param Closure $callback
     * @param array $settings
     * @return RoutePartialGroup|IPartialGroupRoute
     * @throws InvalidArgumentException
     */
    public static function partialGroup(string $url, Closure $callback, array $settings = []): IPartialGroupRoute
    {
        $settings['prefix'] = $url;

        $group = new RoutePartialGroup();
        $group->setSettings($settings);
        $group->setCallback($callback);

        static::router()->addRoute($group);

        return $group;
    }

    /**
     * Alias for the form method
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     * @see SimpleRouter::form
     */
    public static function basic(string $url, $callback, array $settings = null): IRoute
    {
        return static::form($url, $callback, $settings);
    }

    /**
     * This type will route the given url to your callback on the provided request methods.
     * Route the given url to your callback on POST and GET request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     * @see SimpleRouter::form
     */
    public static function form(string $url, $callback, array $settings = null): IRoute
    {
        return static::match([
            Request::REQUEST_TYPE_GET,
            Request::REQUEST_TYPE_POST,
        ], $url, $callback, $settings);
    }

    /**
     * This type will route the given url to your callback on the provided request methods.
     *
     * @param array $requestMethods
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public static function match(array $requestMethods, string $url, $callback, array $settings = null): IRoute
    {
        $route = new RouteUrl($url, $callback);
        $route->setRequestMethods($requestMethods);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        return static::router()->addRoute($route);
    }

    /**
     * This type will route the given url to your callback and allow any type of request method
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public static function all(string $url, $callback, array $settings = null): IRoute
    {
        $route = new RouteUrl($url, $callback);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        return static::router()->addRoute($route);
    }

    /**
     * This route will route request from the given url to the controller.
     *
     * @param string $url
     * @param string $controller
     * @param array|null $settings
     * @return RouteController|IRoute
     */
    public static function controller(string $url, string $controller, array $settings = null): IRoute
    {
        $route = new RouteController($url, $controller);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        return static::router()->addRoute($route);
    }

    /**
     * This type will route all REST-supported requests to different methods in the provided controller.
     *
     * @param string $url
     * @param string $controller
     * @param array|null $settings
     * @return RouteResource|IRoute
     */
    public static function resource(string $url, string $controller, array $settings = null): IRoute
    {
        $route = new RouteResource($url, $controller);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        return static::router()->addRoute($route);
    }

    /**
     * Add exception callback handler.
     *
     * @param Closure $callback
     * @return CallbackExceptionHandler $callbackHandler
     */
    public static function error(Closure $callback): CallbackExceptionHandler
    {
        $callbackHandler = new CallbackExceptionHandler($callback);

        static::router()->addExceptionHandler($callbackHandler);

        return $callbackHandler;
    }

    /**
     * Get url for a route by using either name/alias, class or method name.
     *
     * The name parameter supports the following values:
     * - Route name
     * - Controller/resource name (with or without method)
     * - Controller class name
     *
     * When searching for controller/resource by name, you can use this syntax "route.name@method".
     * You can also use the same syntax when searching for a specific controller-class "MyController@home".
     * If no arguments is specified, it will return the url for the current loaded route.
     *
     * @param string|null $name
     * @param string|array|null $parameters
     * @param array|null $getParams
     * @return Url
     */
    public static function getUrl(?string $name = null, $parameters = null, ?array $getParams = null): Url
    {
        try {
            return static::router()->getUrl($name, $parameters, $getParams);
        } catch (Exception $e) {
            return new Url('/');
        }
    }

    /**
     * Get the request
     *
     * @return Request
     */
    public static function request(): Request
    {
        return static::router()->getRequest();
    }

    /**
     * Get the response object
     *
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
     * Returns the router instance
     *
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
     * Prepends the default namespace to all new routes added.
     *
     * @param ILoadableRoute|IRoute $route
     * @return IRoute
     */
    public static function addDefaultNamespace(IRoute $route): IRoute
    {
        if (static::$defaultNamespace !== null) {
            $route->setNamespace(static::$defaultNamespace);
        }

        return $route;
    }

    /**
     * Changes the rendering behavior of the router.
     * When enabled the router will render all routes that matches.
     * When disabled the router will stop rendering at the first route that matches.
     *
     * @param bool $bool
     */
    public static function enableMultiRouteRendering(bool $bool): void
    {
        static::router()->setRenderMultipleRoutes($bool);
    }

    /**
     * Set custom class-loader class used.
     * @param IClassLoader $classLoader
     */
    public static function setCustomClassLoader(IClassLoader $classLoader): void
    {
        static::router()->setClassLoader($classLoader);
    }

    /**
     * Get default namespace
     * @return string|null
     */
    public static function getDefaultNamespace(): ?string
    {
        return static::$defaultNamespace;
    }

}
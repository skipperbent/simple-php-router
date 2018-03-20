<?php

namespace Pecee\Http;

use Pecee\Http\Input\InputHandler;
use Pecee\SimpleRouter\Route\ILoadableRoute;
use Pecee\SimpleRouter\Route\RouteUrl;
use Pecee\SimpleRouter\SimpleRouter;

class Request
{
    private $data = [];
    protected $headers;
    protected $host;
    protected $url;
    protected $method;
    protected $inputHandler;

    protected $hasRewrite = false;

    /**
     * @var ILoadableRoute|null
     */
    protected $rewriteRoute;
    protected $rewriteUrl;

    /**
     * @var array
     */
    protected $loadedRoutes = [];

    /**
     * Request constructor.
     * @throws \Pecee\Http\Exceptions\MalformedUrlException
     */
    public function __construct()
    {
        $this->parseHeaders();
        $this->setHost($this->getHeader('http-host'));

        // Check if special IIS header exist, otherwise use default.
        $this->setUrl($this->getHeader('unencoded-url', $this->getHeader('request-uri')));

        $this->inputHandler = new InputHandler($this);
        $this->method = strtolower($this->inputHandler->get('_method', $this->getHeader('request-method')));
    }

    protected function parseHeaders(): void
    {
        $this->headers = [];

        foreach ($_SERVER as $key => $value) {
            $this->headers[strtolower($key)] = $value;
            $this->headers[strtolower(str_replace('_', '-', $key))] = $value;
        }

    }

    public function isSecure(): bool
    {
        return $this->getHeader('http-x-forwarded-proto') === 'https' || $this->getHeader('https') !== null || $this->getHeader('server-port') === 443;
    }

    /**
     * @return Url
     */
    public function getUrl(): Url
    {
        return $this->url;
    }

    /**
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Get http basic auth user
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->getHeader('php-auth-user');
    }

    /**
     * Get http basic auth password
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->getHeader('php-auth-pw');
    }

    /**
     * Get all headers
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get id address
     * @return string|null
     */
    public function getIp(): ?string
    {
        if ($this->getHeader('http-cf-connecting-ip') !== null) {
            return $this->getHeader('http-cf-connecting-ip');
        }

        if ($this->getHeader('http-x-forwarded-for') !== null) {
            return $this->getHeader('http-x-forwarded_for');
        }

        return $this->getHeader('remote-addr');
    }

    /**
     * Get remote address/ip
     *
     * @alias static::getIp
     * @return string|null
     */
    public function getRemoteAddr(): ?string
    {
        return $this->getIp();
    }

    /**
     * Get referer
     * @return string|null
     */
    public function getReferer(): ?string
    {
        return $this->getHeader('http-referer');
    }

    /**
     * Get user agent
     * @return string|null
     */
    public function getUserAgent(): ?string
    {
        return $this->getHeader('http-user-agent');
    }

    /**
     * Get header value by name
     *
     * @param string $name
     * @param string|null $defaultValue
     *
     * @return string|null
     */
    public function getHeader($name, $defaultValue = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $defaultValue;
    }

    /**
     * Get input class
     * @return InputHandler
     */
    public function getInputHandler(): InputHandler
    {
        return $this->inputHandler;
    }

    /**
     * Is format accepted
     *
     * @param string $format
     *
     * @return bool
     */
    public function isFormatAccepted($format): bool
    {
        return ($this->getHeader('http-accept') !== null && stripos($this->getHeader('http-accept'), $format) !== false);
    }

    /**
     * Returns true if the request is made through Ajax
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return (strtolower($this->getHeader('http-x-requested-with')) === 'xmlhttprequest');
    }

    /**
     * Get accept formats
     * @return array
     */
    public function getAcceptFormats(): array
    {
        return explode(',', $this->getHeader('http-accept'));
    }

    /**
     * @param string|Url $url
     * @throws \Pecee\Http\Exceptions\MalformedUrlException
     */
    public function setUrl($url): void
    {
        $this->url = ($url instanceof Url) ? $url : new Url($url);
    }

    /**
     * @param string $host
     */
    public function setHost($host): void
    {
        $this->host = $host;
    }

    /**
     * @param string $method
     */
    public function setMethod($method): void
    {
        $this->method = $method;
    }

    /**
     * Set rewrite route
     *
     * @param ILoadableRoute $route
     * @return static
     */
    public function setRewriteRoute(ILoadableRoute $route): self
    {
        $this->hasRewrite = true;
        $this->rewriteRoute = SimpleRouter::addDefaultNamespace($route);

        return $this;
    }

    /**
     * Get rewrite route
     *
     * @return ILoadableRoute|null
     */
    public function getRewriteRoute(): ?ILoadableRoute
    {
        return $this->rewriteRoute;
    }

    /**
     * Get rewrite url
     *
     * @return string|null
     */
    public function getRewriteUrl(): ?string
    {
        return $this->rewriteUrl;
    }

    /**
     * Set rewrite url
     *
     * @param string $rewriteUrl
     * @return static
     */
    public function setRewriteUrl(string $rewriteUrl): self
    {
        $this->hasRewrite = true;
        $this->rewriteUrl = rtrim($rewriteUrl, '/') . '/';

        return $this;
    }

    /**
     * Set rewrite callback
     * @param string|\Closure $callback
     * @return static
     */
    public function setRewriteCallback($callback): self
    {
        $this->hasRewrite = true;

        return $this->setRewriteRoute(new RouteUrl($this->getUrl()->getPath(), $callback));
    }

    /**
     * Get loaded route
     * @return ILoadableRoute|null
     */
    public function getLoadedRoute(): ?ILoadableRoute
    {
        return (\count($this->loadedRoutes) > 0) ? end($this->loadedRoutes) : null;
    }

    /**
     * Get all loaded routes
     *
     * @return array
     */
    public function getLoadedRoutes(): array
    {
        return $this->loadedRoutes;
    }

    /**
     * Set loaded routes
     *
     * @param array $routes
     * @return static
     */
    public function setLoadedRoutes(array $routes): self
    {
        $this->loadedRoutes = $routes;

        return $this;
    }

    /**
     * Added loaded route
     *
     * @param ILoadableRoute $route
     * @return static
     */
    public function addLoadedRoute(ILoadableRoute $route): self
    {
        $this->loadedRoutes[] = $route;

        return $this;
    }

    public function hasRewrite(): bool
    {
        return $this->hasRewrite;
    }

    public function setHasRewrite($value): self
    {
        $this->hasRewrite = $value;

        return $this;
    }

    public function isRewrite($url): bool
    {
        return ($this->rewriteUrl === $url);
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->data);
    }

    public function __set($name, $value = null)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

}
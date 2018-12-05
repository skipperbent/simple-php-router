<?php

namespace Pecee\Http;

use Pecee\Http\Input\InputHandler;
use Pecee\SimpleRouter\SimpleRouter;
use Pecee\SimpleRouter\Route\RouteUrl;
use Pecee\SimpleRouter\Route\ILoadableRoute;
use Pecee\Http\Exceptions\MalformedUrlException;

/**
 * Class Request
 *
 * @package Pecee\Http
 */
class Request
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    protected $headers = [];

    protected $host;
    protected $url;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var InputHandler
     */
    protected $inputHandler;

    /**
     * @var bool
     */
    protected $hasPendingRewrite = false;
    protected $rewriteRoute;
    protected $rewriteUrl;

    /**
     * @var array
     */
    protected $loadedRoutes = [];

    /**
     * Request constructor.
     * @throws MalformedUrlException
     */
    public function __construct()
    {
        foreach ($_SERVER as $key => $value) {
            $this->headers[strtolower($key)] = $value;
            $this->headers[strtolower(str_replace('_', '-', $key))] = $value;
        }

        $this->setHost($this->getHeader('http-host'));
        // Check if special IIS header exist, otherwise use default.
        $this->setUrl(new Url($this->getHeader('unencoded-url', $this->getHeader('request-uri'))));
        $this->method = strtolower($this->getHeader('request-method'));
        $this->inputHandler = new InputHandler($this);
        $this->method = strtolower($this->inputHandler->value('_method', $this->getHeader('request-method')));
    }

    /**
     * @return bool
     */
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
     * @return Url
     */
    public function getUrlCopy(): Url
    {
        return clone $this->url;
    }

    /**
     * @return null|string
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return null|string
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * @return null|string
     */
    public function getUser(): ?string
    {
        return $this->getHeader('php-auth-user');
    }

    /**
     * @return null|string
     */
    public function getPassword(): ?string
    {
        return $this->getHeader('php-auth-pw');
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return null|string
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
     * @return null|string
     */
    public function getRemoteAddr(): ?string
    {
        return $this->getIp();
    }

    /**
     * @return null|string
     */
    public function getReferer(): ?string
    {
        return $this->getHeader('http-referer');
    }

    /**
     * @return null|string
     */
    public function getUserAgent(): ?string
    {
        return $this->getHeader('http-user-agent');
    }

    /**
     * @param $name
     * @param null $defaultValue
     * @return null|string
     */
    public function getHeader($name, $defaultValue = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $defaultValue;
    }

    /**
     * @return InputHandler
     */
    public function getInputHandler(): InputHandler
    {
        return $this->inputHandler;
    }

    /**
     * @param $format
     * @return bool
     */
    public function isFormatAccepted($format): bool
    {
        return ($this->getHeader('http-accept') !== null && stripos($this->getHeader('http-accept'), $format) !== false);
    }

    /**
     * @return bool
     */
    public function isAjax(): bool
    {
        return (strtolower($this->getHeader('http-x-requested-with')) === 'xmlhttprequest');
    }

    /**
     * @return array
     */
    public function getAcceptFormats(): array
    {
        return explode(',', $this->getHeader('http-accept'));
    }

    /**
     * @param Url $url
     */
    public function setUrl(Url $url): void
    {
        $this->url = $url;

        if ($this->url->getHost() === null) {
            $this->url->setHost((string)$this->getHost());
        }
    }

    /**
     * @param null|string $host
     */
    public function setHost(?string $host): void
    {
        $this->host = $host;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void
    {
        $this->method = strtolower($method);
    }

    /**
     * @param ILoadableRoute $route
     * @return Request
     */
    public function setRewriteRoute(ILoadableRoute $route): self
    {
        $this->hasPendingRewrite = true;
        $this->rewriteRoute = SimpleRouter::addDefaultNamespace($route);

        return $this;
    }

    /**
     * @return null|ILoadableRoute
     */
    public function getRewriteRoute(): ?ILoadableRoute
    {
        return $this->rewriteRoute;
    }

    /**
     * @return null|string
     */
    public function getRewriteUrl(): ?string
    {
        return $this->rewriteUrl;
    }

    /**
     * @param string $rewriteUrl
     * @return Request
     */
    public function setRewriteUrl(string $rewriteUrl): self
    {
        $this->hasPendingRewrite = true;
        $this->rewriteUrl = rtrim($rewriteUrl, '/') . '/';

        return $this;
    }

    /**
     * @param $callback
     * @return Request
     */
    public function setRewriteCallback($callback): self
    {
        $this->hasPendingRewrite = true;

        return $this->setRewriteRoute(new RouteUrl($this->getUrl()->getPath(), $callback));
    }

    /**
     * @return null|ILoadableRoute
     */
    public function getLoadedRoute(): ?ILoadableRoute
    {
        return (\count($this->loadedRoutes) > 0) ? end($this->loadedRoutes) : null;
    }

    /**
     * @return array
     */
    public function getLoadedRoutes(): array
    {
        return $this->loadedRoutes;
    }

    /**
     * @param array $routes
     * @return Request
     */
    public function setLoadedRoutes(array $routes): self
    {
        $this->loadedRoutes = $routes;

        return $this;
    }

    /**
     * @param ILoadableRoute $route
     * @return Request
     */
    public function addLoadedRoute(ILoadableRoute $route): self
    {
        $this->loadedRoutes[] = $route;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasPendingRewrite(): bool
    {
        return $this->hasPendingRewrite;
    }

    /**
     * @param bool $boolean
     * @return Request
     */
    public function setHasPendingRewrite(bool $boolean): self
    {
        $this->hasPendingRewrite = $boolean;

        return $this;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->data) === true;
    }

    /**
     * @param $name
     * @param null $value
     */
    public function __set($name, $value = null)
    {
        $this->data[$name] = $value;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }
}

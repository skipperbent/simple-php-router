<?php
namespace Pecee\Http;

use Pecee\Http\Input\Input;
use Pecee\SimpleRouter\Route\ILoadableRoute;
use Pecee\SimpleRouter\Route\RouteUrl;
use Pecee\SimpleRouter\SimpleRouter;

class Request
{
    private $data = [];
    protected $headers;
    protected $host;
    protected $uri;
    protected $method;
    protected $input;

    /**
     * @var ILoadableRoute|null
     */
    protected $rewriteRoute;
    protected $rewriteUrl;

    /**
     * @var ILoadableRoute|null
     */
    protected $loadedRoute;

    public function __construct()
    {
        $this->parseHeaders();
        $this->host = $this->getHeader('http-host');
        $this->uri = $this->getHeader('request-uri');
        $this->input = new Input($this);
        $this->method = strtolower($this->input->get('_method', $this->getHeader('request-method'), 'post'));
    }

    protected function parseHeaders()
    {
        $this->headers = [];

        $max = count($_SERVER) - 1;
        $keys = array_keys($_SERVER);

        for ($i = $max; $i >= 0; $i--) {
            $key = $keys[$i];
            $value = $_SERVER[$key];

            $this->headers[strtolower($key)] = $value;
            $this->headers[strtolower(str_replace('_', '-', $key))] = $value;
        }

    }

    public function isSecure()
    {
        return $this->getHeader('http-x-forwarded-proto') === 'https' || $this->getHeader('https') !== null || $this->getHeader('server-port') === 443;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get http basic auth user
     * @return string|null
     */
    public function getUser()
    {
        return $this->getHeader('php-auth-user');
    }

    /**
     * Get http basic auth password
     * @return string|null
     */
    public function getPassword()
    {
        return $this->getHeader('php-auth-pw');
    }

    /**
     * Get all headers
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get id address
     * @return string
     */
    public function getIp()
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
     * @return string
     */
    public function getRemoteAddr()
    {
        return $this->getIp();
    }

    /**
     * Get referer
     * @return string
     */
    public function getReferer()
    {
        return $this->getHeader('http-referer');
    }

    /**
     * Get user agent
     * @return string
     */
    public function getUserAgent()
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
    public function getHeader($name, $defaultValue = null)
    {
        if (isset($this->headers[strtolower($name)])) {
            return $this->headers[strtolower($name)];
        }

        $max = count($_SERVER) - 1;
        $keys = array_keys($_SERVER);

        for ($i = $max; $i >= 0; $i--) {

            $key = $keys[$i];
            $name = $_SERVER[$key];

            if ($key === $name) {
                return $name;
            }
        }

        return $defaultValue;
    }

    /**
     * Get input class
     * @return Input
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Is format accepted
     *
     * @param string $format
     *
     * @return bool
     */
    public function isFormatAccepted($format)
    {
        return ($this->getHeader('http-accept') !== null && stripos($this->getHeader('http-accept'), $format) > -1);
    }

    /**
     * Get accept formats
     * @return array
     */
    public function getAcceptFormats()
    {
        return explode(',', $this->getHeader('http-accept'));
    }

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * Set rewrite route
     *
     * @param ILoadableRoute $route
     * @return static
     */
    public function setRewriteRoute(ILoadableRoute $route)
    {
        $this->rewriteRoute = $route;

        $callback = $route->getCallback();

        /* Only add default namespace on relative callbacks */
        if ($callback === null || $callback[0] !== '\\') {

            $namespace = SimpleRouter::getDefaultNamespace();

            if ($namespace !== null) {

                if ($this->rewriteRoute->getNamespace() !== null) {
                    $namespace .= '\\' . $this->rewriteRoute->getNamespace();
                }

                $this->rewriteRoute->setDefaultNamespace($namespace);

            }

        }

        return $this;
    }

    /**
     * Get rewrite route
     *
     * @return ILoadableRoute|null
     */
    public function getRewriteRoute()
    {
        return $this->rewriteRoute;
    }

    /**
     * Get rewrite url
     *
     * @return string
     */
    public function getRewriteUrl()
    {
        return $this->rewriteUrl;
    }

    /**
     * Set rewrite url
     *
     * @param string $rewriteUrl
     * @return static
     */
    public function setRewriteUrl($rewriteUrl)
    {
        $this->rewriteUrl = $rewriteUrl;

        return $this;
    }

    /**
     * Set rewrite callback
     * @param string $callback
     * @return static
     */
    public function setRewriteCallback($callback)
    {
        return $this->setRewriteRoute(new RouteUrl($this->uri, $callback));
    }

    /**
     * Get loaded route
     * @return ILoadableRoute|null
     */
    public function getLoadedRoute()
    {
        return $this->loadedRoute;
    }

    /**
     * Set loaded route
     *
     * @param ILoadableRoute $route
     * @return static
     */
    public function setLoadedRoute(ILoadableRoute $route)
    {
        $this->loadedRoute = $route;

        return $this;
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
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

}
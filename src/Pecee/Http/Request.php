<?php

namespace Pecee\Http;

use Pecee\Http\Exceptions\MalformedUrlException;
use Pecee\Http\Input\InputHandler;
use Pecee\Http\Middleware\BaseCsrfVerifier;
use Pecee\SimpleRouter\Route\ILoadableRoute;
use Pecee\SimpleRouter\Route\RouteUrl;
use Pecee\SimpleRouter\SimpleRouter;

class Request
{

    public const PLATFORM_LINUX = 'linux';
    public const PLATFORM_WINDOWS = 'windows';
    public const PLATFORM_WINDOWS_PHONE = 'windows phone';
    public const PLATFORM_MAC = 'mac os';
    public const PLATFORM_IPAD = 'ipad os';
    public const PLATFORM_IOS = 'ios';
    public const PLATFORM_APPLE_WATCH = 'apple watch';
    public const PLATFORM_ANDROID = 'android';
    public const PLATFORM_CHROME = 'chrome os';

    public const BROWSER_CHROME = 'chrome';
    public const BROWSER_FIREFOX = 'firefox';
    public const BROWSER_FIREFOX_TOR = 'tor';
    public const BROWSER_SAFARI = 'safari';
    public const BROWSER_OPERA = 'opera';
    public const BROWSER_EDGE = 'edge';
    public const BROWSER_BRAVE = 'brave';
    public const BROWSER_IE = 'ie';
    public const BROWSER_VIVALDI = 'vivaldi';
    public const BROWSER_YANDEX = 'yandex';

    public static $platforms = [
        self::PLATFORM_LINUX,
        self::PLATFORM_WINDOWS,
        self::PLATFORM_WINDOWS_PHONE,
        self::PLATFORM_MAC,
        self::PLATFORM_IPAD,
        self::PLATFORM_IOS,
        self::PLATFORM_APPLE_WATCH,
        self::PLATFORM_ANDROID,
        self::PLATFORM_CHROME
    ];

    public static $browsers = [
        self::BROWSER_CHROME,
        self::BROWSER_FIREFOX,
        self::BROWSER_FIREFOX_TOR,
        self::BROWSER_SAFARI,
        self::BROWSER_OPERA,
        self::BROWSER_EDGE,
        self::BROWSER_BRAVE,
        self::BROWSER_IE,
        self::BROWSER_VIVALDI,
        self::BROWSER_YANDEX
    ];

    public const REQUEST_TYPE_GET = 'get';
    public const REQUEST_TYPE_POST = 'post';
    public const REQUEST_TYPE_PUT = 'put';
    public const REQUEST_TYPE_PATCH = 'patch';
    public const REQUEST_TYPE_OPTIONS = 'options';
    public const REQUEST_TYPE_DELETE = 'delete';
    public const REQUEST_TYPE_HEAD = 'head';

    public const CONTENT_TYPE_JSON = 'application/json';
    public const CONTENT_TYPE_FORM_DATA = 'multipart/form-data';
    public const CONTENT_TYPE_X_FORM_ENCODED = 'application/x-www-form-urlencoded';

    public const FORCE_METHOD_KEY = '_method';

    /**
     * All request-types
     * @var string[]
     */
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
     * Post request-types.
     * @var string[]
     */
    public static $requestTypesPost = [
        self::REQUEST_TYPE_POST,
        self::REQUEST_TYPE_PUT,
        self::REQUEST_TYPE_PATCH,
        self::REQUEST_TYPE_DELETE
    ];

    /**
     * Additional data
     *
     * @var array
     */
    private $data = [];

    /**
     * Server headers
     * @var array
     */
    protected $headers = [];

    /**
     * Request ContentType
     * @var string
     */
    protected $contentType;

    /**
     * Request host
     * @var string
     */
    protected $host;

    /**
     * Current request url
     * @var Url
     */
    protected $url;

    /**
     * Request method
     * @var string
     */
    protected $method;

    /**
     * Input handler
     * @var InputHandler
     */
    protected $inputHandler;

    /**
     * Defines if request has pending rewrite
     * @var bool
     */
    protected $hasPendingRewrite = false;

    /**
     * @var ILoadableRoute|null
     */
    protected $rewriteRoute;

    /**
     * Rewrite url
     * @var string|null
     */
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
            $this->headers[str_replace('_', '-', strtolower($key))] = $value;
        }

        $this->setHost($this->getHeader('http-host'));

        // Check if special IIS header exist, otherwise use default.
        $this->setUrl(new Url($this->getFirstHeader(['unencoded-url', 'request-uri'])));
        $this->setContentType((string)$this->getHeader('content-type'));
        $this->setMethod((string)($_POST[static::FORCE_METHOD_KEY] ?? $this->getHeader('request-method')));
        $this->inputHandler = new InputHandler($this);
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
     * Copy url object
     *
     * @return Url
     */
    public function getUrlCopy(): Url
    {
        return clone $this->url;
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
     * RegEx created using https://www.whatismybrowser.com/guides/the-latest-user-agent/ and https://developers.whatismybrowser.com/useragents/parse/
     * Tor User agent is described in section "Tor + Fingerprinting" on https://blog.torproject.org/browser-fingerprinting-introduction-and-challenges-ahead
     * @return string|null
     */
    public function getBrowser(): ?string{
        $user_agent = $this->getUserAgent();
        if(preg_match('/Mozilla\/5\.0 \(Windows NT \d\d\.\d; rv:\d\d\.\d\) Gecko\/20100101 Firefox\/\d\d\.0$/i', $user_agent)){
            return self::BROWSER_FIREFOX_TOR;
        }else if(preg_match('/Trident\/|MSIE/i', $user_agent) && !preg_match('/Opera|OPR/i', $user_agent)){
            return self::BROWSER_IE;
        }else if(preg_match('/Firefox|FxiOS/i', $user_agent)) {
            return self::BROWSER_FIREFOX;
        }else if(preg_match('/Edg/i', $user_agent)){//Edg, EdgA, Edge, EdgiOS   -> /Edg(e|A|iOS)?/i
            return self::BROWSER_EDGE;
        }else if(preg_match('/Vivaldi/i', $user_agent)){
            return self::BROWSER_VIVALDI;
        }else if(preg_match('/YaBrowser/i', $user_agent)){
            return self::BROWSER_YANDEX;
        }else if(preg_match('/Opera|OPR/i', $user_agent)) {
            return self::BROWSER_OPERA;
        }else if(preg_match('/Brave/i', $user_agent)) {
            return self::BROWSER_BRAVE;
        }else if(preg_match('/Chrome|CriOS/i', $user_agent)) {
            return self::BROWSER_CHROME;
        }else if(preg_match('/Safari/i', $user_agent)) {
            return self::BROWSER_SAFARI;
        }
        return null;
    }

    /**
     * @return string|null
     */
    public function getPlatform(): ?string{
        $user_agent = $this->getUserAgent();
        if(preg_match('/CrOS|Chromebook/i', $user_agent)){
            return self::PLATFORM_CHROME;
        }else if(preg_match('/Watch OS|watchOS/i', $user_agent)){
            return self::PLATFORM_APPLE_WATCH;
        }else if(preg_match('/iPad/i', $user_agent) && preg_match('/Mac OS/i', $user_agent)){
            return self::PLATFORM_IPAD;
        }else if(preg_match('/iPhone OS/i', $user_agent)){
            return self::PLATFORM_IOS;
        }else if(preg_match('/Mac OS|Mac_PowerPC/i', $user_agent)){//TODO Ipad (new versions) has the same user_agent -> /Macintosh/i
            return self::PLATFORM_MAC;
        }else if(preg_match('/Windows Phone/i', $user_agent)){
            return self::PLATFORM_WINDOWS_PHONE;
        }else if(preg_match('/Windows/i', $user_agent)){
            return self::PLATFORM_WINDOWS;
        }else if(preg_match('/Android/i', $user_agent)){
            return self::PLATFORM_ANDROID;
        }else if(preg_match('/Linux/i', $user_agent)){
            return self::PLATFORM_LINUX;
        }
        return null;
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
     * Get the csrf token
     * @return string|null
     */
    public function getCsrfToken(): ?string
    {
        return $this->getHeader(BaseCsrfVerifier::HEADER_KEY);
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
     * If $safe is false, this function will detect Proxys. But the user can edit this header to whatever he wants!
     * https://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php#comment-25086804
     * @param bool $safeMode When enabled, only safe non-spoofable headers will be returned. Note this can cause issues when using proxy.
     * @return string|null
     */
    public function getIp(bool $safeMode = false): ?string
    {
        $headers = ['remote-addr'];
        if($safeMode === false) {
            $headers = array_merge($headers, [
                'http-cf-connecting-ip',
                'http-client-ip',
                'http-x-forwarded-for',
            ]);
        }

        return $this->getFirstHeader($headers);
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
     * @param string $name Name of the header.
     * @param string|null $defaultValue Value to be returned if header is not found.
     * @param bool $tryParse When enabled the method will try to find the header from both from client (http) and server-side variants, if the header is not found.
     *
     * @return string|null
     */
    public function getHeader(string $name, $defaultValue = null, $tryParse = true): ?string
    {
        $name = strtolower($name);
        $header = $this->headers[$name] ?? null;

        if ($tryParse === true && $header === null) {
            if (strpos($name, 'http-') === 0) {
                // Trying to find client header variant which was not found, searching for header variant without http- prefix.
                $header = $this->headers[str_replace('http-', '', $name)] ?? null;
            } else {
                // Trying to find server variant which was not found, searching for client variant with http- prefix.
                $header = $this->headers['http-' . $name] ?? null;
            }
        }

        return $header ?? $defaultValue;
    }

    /**
     * Will try to find first header from list of headers.
     *
     * @param array $headers
     * @param mixed|null $defaultValue
     * @return mixed|null
     */
    public function getFirstHeader(array $headers, $defaultValue = null)
    {
        foreach($headers as $header) {
            $header = $this->getHeader($header);
            if($header !== null) {
                return $header;
            }
        }

        return $defaultValue;
    }

    /**
     * Get request content-type
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * Set request content-type
     * @param string $contentType
     * @return $this
     */
    protected function setContentType(string $contentType): self
    {
        if(strpos($contentType, ';') > 0) {
            $this->contentType = strtolower(substr($contentType, 0, strpos($contentType, ';')));
        } else {
            $this->contentType = strtolower($contentType);
        }

        return $this;
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
    public function isFormatAccepted(string $format): bool
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
     * Returns true when request-method is type that could contain data in the page body.
     * 
     * @return bool
     */
    public function isPostBack(): bool
    {
        return in_array($this->getMethod(), static::$requestTypesPost, true);
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
     * @param string|null $host
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
     * Set rewrite route
     *
     * @param ILoadableRoute $route
     * @return static
     */
    public function setRewriteRoute(ILoadableRoute $route): self
    {
        $this->hasPendingRewrite = true;
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
        $this->hasPendingRewrite = true;
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
        $this->hasPendingRewrite = true;

        return $this->setRewriteRoute(new RouteUrl($this->getUrl()->getPath(), $callback));
    }

    /**
     * Get loaded route
     * @return ILoadableRoute|null
     */
    public function getLoadedRoute(): ?ILoadableRoute
    {
        return (count($this->loadedRoutes) > 0) ? end($this->loadedRoutes) : null;
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

    /**
     * Returns true if the request contains a rewrite
     *
     * @return bool
     */
    public function hasPendingRewrite(): bool
    {
        return $this->hasPendingRewrite;
    }

    /**
     * Defines if the current request contains a rewrite.
     *
     * @param bool $boolean
     * @return Request
     */
    public function setHasPendingRewrite(bool $boolean): self
    {
        $this->hasPendingRewrite = $boolean;
        return $this;
    }

    public function __isset($name): bool
    {
        return array_key_exists($name, $this->data) === true;
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
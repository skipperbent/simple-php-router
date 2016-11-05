<?php
namespace Pecee\Http;

use Pecee\Http\Input\Input;

class Request {

    protected static $instance;

    protected $data;

    /**
     * Return new instance
     * @return static
     */
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->data = array();
        $this->host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : array();
        $this->uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : array();
        $this->method = (isset($_POST['_method'])) ? strtolower($_POST['_method']) : (isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : array());
        $this->headers = $this->getAllHeaders();
        $this->input = new Input();
    }

    protected function getAllHeaders() {
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $headers[strtolower(str_replace('_', '-', substr($name, 5)))] = $value;
            }
        }
        return $headers;
    }

    public function getIsSecure() {
        if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }
        return isset($_SERVER['HTTPS']) ? true : (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === 443);
    }

    /**
     * @return string
     */
    public function getUri() {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Get http basic auth user
     * @return string|null
     */
    public function getUser() {
        return (isset($_SERVER['PHP_AUTH_USER'])) ? $_SERVER['PHP_AUTH_USER']: null;
    }

    /**
     * Get http basic auth password
     * @return string|null
     */
    public function getPassword() {
        return (isset($_SERVER['PHP_AUTH_PW'])) ? $_SERVER['PHP_AUTH_PW']: null;
    }

    /**
     * Get headers
     * @return array
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Get id address
     * @return string
     */
    public function getIp() {
        if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        return ((isset($_SERVER['HTTP_X_FORWARDED_FOR']) && strlen($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null);
    }

    /**
     * Get referer
     * @return string
     */
    public function getReferer() {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    }

    /**
     * Get user agent
     * @return string
     */
    public function getUserAgent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }

    /**
     * Get header value by name
     * @param string $name
     * @return string|null
     */
    public function getHeader($name) {
        return (isset($this->headers[strtolower($name)])) ? $this->headers[strtolower($name)] : null;
    }

    /**
     * Get input class
     * @return Input
     */
    public function getInput() {
        return $this->input;
    }

    public function isFormatAccepted($format) {
        return (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], $format) > -1);
    }

    public function getAcceptFormats() {
        if(isset($_SERVER['HTTP_ACCEPT'])) {
            return explode(',', $_SERVER['HTTP_ACCEPT']);
        }
        return array();
    }

    public function __set($name, $value = null) {
        $this->data[$name] = $value;
    }

    public function __get($name) {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * Get the currently loaded route.
     * @return \Pecee\SimpleRouter\RouterEntry
     */
    public function getLoadedRoute() {
        return $this->loadedRoute;
    }

    /**
     * @param mixed $uri
     */
    public function setUri($uri) {
        $this->uri = $uri;
    }

    /**
     * @param mixed $host
     */
    public function setHost($host) {
        $this->host = $host;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method) {
        $this->method = $method;
    }

}
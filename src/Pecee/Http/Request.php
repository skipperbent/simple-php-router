<?php
namespace Pecee\Http;

use Pecee\Http\Input\Input;

class Request {

    protected $data = array();
    protected $headers;
    protected $host;
    protected $uri;
    protected $method;
    protected $input;


    public function __construct() {
        $this->parseHeaders();
        $this->input = new Input($this);

        $this->host = $this->getHeader('http_host');;
        $this->uri = $this->getHeader('request_uri');
        $this->method = strtolower($this->input->post->findFirst('_method', $this->getHeader('request_method')));
    }

    protected function parseHeaders() {
        $this->headers = array();

        foreach ($_SERVER as $name => $value) {
            $this->headers[strtolower($name)] = $value;
        }
    }

    public function isSecure() {
        if($this->getHeader('http_x_forwarded_proto') === 'https') {
            return true;
        }

        if($this->getHeader('https') !== null) {
            return true;
        }

        return ($this->getHeader('server_port') === 443);
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
        return $this->getHeader('php_auth_user');
    }

    /**
     * Get http basic auth password
     * @return string|null
     */
    public function getPassword() {
        return $this->getHeader('php_auth_pw');
    }

    /**
     * Get all headers
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
        if($this->getHeader('http_cf_connecting_ip') !== null) {
            return $this->getHeader('http_cf_connecting_ip');
        }

        if($this->getHeader('http_x_forwarded_for') !== null && strlen($this->getHeader('http_x_forwarded_for'))) {
            return $this->getHeader('http_x_forwarded_for');
        }

        return $this->getHeader('remote_addr');
    }

    /**
     * Get referer
     * @return string
     */
    public function getReferer() {
        return $this->getHeader('http_referer');
    }

    /**
     * Get user agent
     * @return string
     */
    public function getUserAgent() {
        return $this->getHeader('http_user_agent');
    }

    /**
     * Get header value by name
     * @param string $name
     * @param object|null $defaultValue
     * @return string|null
     */
    public function getHeader($name, $defaultValue = null) {
        return isset($this->headers[strtolower($name)]) ? $this->headers[strtolower($name)] : $defaultValue;
    }

    /**
     * Get input class
     * @return Input
     */
    public function getInput() {
        return $this->input;
    }

    /**
     * Is format accepted
     * @param string $format
     * @return bool
     */
    public function isFormatAccepted($format) {
        return ($this->getHeader('http_accept') !== null && stripos($this->getHeader('http_accept'), $format) > -1);
    }

    /**
     * Get accept formats
     * @return array
     */
    public function getAcceptFormats() {
        return explode(',', $this->getHeader('http_accept'));
    }

    /**
     * @param string $uri
     */
    public function setUri($uri) {
        $this->uri = $uri;
    }

    /**
     * @param string $host
     */
    public function setHost($host) {
        $this->host = $host;
    }

    /**
     * @param string $method
     */
    public function setMethod($method) {
        $this->method = $method;
    }

    public function __set($name, $value = null) {
        $this->data[$name] = $value;
    }

    public function __get($name) {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

}
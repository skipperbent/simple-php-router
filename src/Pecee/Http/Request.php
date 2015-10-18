<?php
namespace Pecee\Http;

class Request {

    protected $uri;
    protected $host;
    protected $method;

    public function __construct() {
        $this->host = $_SERVER['HTTP_HOST'];
        $this->uri = rtrim($_SERVER['REQUEST_URI'], '/') . '/';
        $this->method = (isset($_POST['_method'])) ? strtolower($_POST['_method']) : strtolower($_SERVER['REQUEST_METHOD']);
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
        $data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST']);
        return (isset($data['username'])) ? $data['username'] : null;
    }

}
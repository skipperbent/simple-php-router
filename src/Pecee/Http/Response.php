<?php
namespace Pecee\Http;

class Response
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Set the http status code
     *
     * @param int $code
     * @return static
     */
    public function httpCode($code)
    {
        http_response_code($code);

        return $this;
    }

    /**
     * Redirect the response
     *
     * @param string $url
     * @param int $httpCode
     */
    public function redirect($url, $httpCode = null)
    {
        if ($httpCode !== null) {
            $this->httpCode($httpCode);
        }

        $this->header('location: ' . $url);
        die();
    }

    public function refresh()
    {
        $this->redirect($this->request->getUri());
    }

    /**
     * Add http authorisation
     * @param string $name
     * @return static
     */
    public function auth($name = '')
    {
        $this->headers([
            'WWW-Authenticate: Basic realm="' . $name . '"',
            'HTTP/1.0 401 Unauthorized',
        ]);

        return $this;
    }

    public function cache($eTag, $lastModified = 2592000)
    {

        $this->headers([
            'Cache-Control: public',
            'Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'Etag: ' . $eTag,
        ]);

        $httpModified = $this->request->getHeader('http-if-modified-since');
        $httpIfNoneMatch = $this->request->getHeader('http-if-none-match');

        if (($httpIfNoneMatch !== null && $httpIfNoneMatch === $eTag) || ($httpModified !== null && strtotime($httpModified) === $lastModified)) {

            $this->header('HTTP/1.1 304 Not Modified');

            exit();
        }

        return $this;
    }

    /**
     * Json encode array
     * @param array $value
     */
    public function json(array $value)
    {
        $this->header('Content-Type: application/json');
        echo json_encode($value);
        die();
    }

    /**
     * Add header to response
     * @param string $value
     * @return static
     */
    public function header($value)
    {
        header($value);

        return $this;
    }

    /**
     * Add multiple headers to response
     * @param array $headers
     * @return static
     */
    public function headers(array $headers)
    {
        foreach ($headers as $header) {
            $this->header($header);
        }

        return $this;
    }

}
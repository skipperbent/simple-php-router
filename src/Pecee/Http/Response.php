<?php

namespace Pecee\Http;

use Pecee\Exceptions\InvalidArgumentException;

/**
 * Class Response
 *
 * @package Pecee\Http
 */
class Response
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * Response constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param int $code
     * @return static
     */
    public function httpCode(int $code): self
    {
        http_response_code($code);

        return $this;
    }

    /**
     * @param string $url
     * @param int|null $httpCode
     */
    public function redirect(string $url, ?int $httpCode = null): void
    {
        if ($httpCode !== null) {
            $this->httpCode($httpCode);
        }

        $this->header('location: ' . $url);
        exit(0);
    }

    public function refresh(): void
    {
        $this->redirect($this->request->getUrl()->getOriginalUrl());
    }

    /**
     * @param string $name
     * @return static
     */
    public function auth(string $name = ''): self
    {
        $this->headers([
            'WWW-Authenticate: Basic realm="' . $name . '"',
            'HTTP/1.0 401 Unauthorized',
        ]);

        return $this;
    }

    /**
     * @param string $eTag
     * @param int $lastModifiedTime
     * @return Response
     */
    public function cache(string $eTag, int $lastModifiedTime = 2592000): self
    {
        $this->headers([
            'Cache-Control: public',
            sprintf('Last-Modified: %s GMT', gmdate('D, d M Y H:i:s', $lastModifiedTime)),
            sprintf('Etag: %s', $eTag),
        ]);

        $httpModified = $this->request->getHeader('http-if-modified-since');
        $httpIfNoneMatch = $this->request->getHeader('http-if-none-match');

        if (($httpIfNoneMatch !== null && $httpIfNoneMatch === $eTag) || ($httpModified !== null && strtotime($httpModified) === $lastModifiedTime)) {

            $this->header('HTTP/1.1 304 Not Modified');
            exit(0);
        }

        return $this;
    }

    /**
     * @param $value
     * @param int|null $options
     * @param int $dept
     */
    public function json($value, ?int $options = null, int $dept = 512): void
    {
        if (($value instanceof \JsonSerializable) === false && \is_array($value) === false) {
            throw new InvalidArgumentException('Invalid type for parameter "value". Must be of type array or object implementing the \JsonSerializable interface.');
        }

        $this->header('Content-Type: application/json; charset=utf-8');
        echo json_encode($value, $options, $dept);
        exit(0);
    }

    /**
     * @param string $value
     * @return static
     */
    public function header(string $value): self
    {
        header($value);

        return $this;
    }

    /**
     * @param array $headers
     * @return Response
     */
    public function headers(array $headers): self
    {
        foreach ($headers as $header) {
            $this->header($header);
        }

        return $this;
    }

}
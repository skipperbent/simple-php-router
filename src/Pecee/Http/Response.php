<?php

namespace Pecee\Http;

use JsonSerializable;
use Pecee\Exceptions\InvalidArgumentException;
use Pecee\Http\Exceptions\JsonException;

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
    public function httpCode(int $code): self
    {
        http_response_code($code);

        return $this;
    }

    /**
     * Redirect the response
     *
     * @param string $url
     * @param ?int $httpCode
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
     * Add http authorisation
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
     * @param array|JsonSerializable $data
     * @param array|JsonSerializable $originalValue
     * @param int|null $options
     * @param int $dept
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    private function setUpJsonResponse($data, $originalValue, ?int $options = null, int $dept = 512): void
    {
        if (($originalValue instanceof JsonSerializable) === false && \is_array($originalValue) === false) {
            throw new InvalidArgumentException('Invalid type for parameter "value". Must be of type array or object implementing the \JsonSerializable interface.');
        }

        $json = json_encode($data, $options, $dept);
        if($json === false){
            throw new JsonException('Failed to encode data');
        }
        $this->header('Content-Type: application/json; charset=utf-8');
        echo $json;
        exit(0);
    }

    /**
     * Json encode and print response.
     * @param array|JsonSerializable $value
     * @param ?int $options JSON options Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT, JSON_PRESERVE_ZERO_FRACTION, JSON_UNESCAPED_UNICODE, JSON_PARTIAL_OUTPUT_ON_ERROR.
     * @param int $dept JSON debt.
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function json($value, ?int $options = null, int $dept = 512): void
    {
        $this->setUpJsonResponse($value, $value, $options, $dept);
    }

    /**
     * Json encode and print successful response.
     * @param array|JsonSerializable $data
     * @param int $code - response code
     * @param ?int $options JSON options Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT, JSON_PRESERVE_ZERO_FRACTION, JSON_UNESCAPED_UNICODE, JSON_PARTIAL_OUTPUT_ON_ERROR.
     * @param int $dept JSON debt.
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function apiSuccess($data = array(), $code = 200, ?int $options = null, int $dept = 512): void
    {
        $this->setUpJsonResponse(array(
            'success' => true,
            'code' => $code,
            'data' => $data
        ), $data, $options, $dept);
    }

    /**
     * Json encode and print error response.
     * @param string $message - error message
     * @param int $code - response code
     * @param array $errors - a list of errors
     * @param ?int $options JSON options Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT, JSON_PRESERVE_ZERO_FRACTION, JSON_UNESCAPED_UNICODE, JSON_PARTIAL_OUTPUT_ON_ERROR.
     * @param int $dept JSON debt.
     * @throws JsonException
     */
    public function apiError(string $message, $code = 400, array $errors = array(), ?int $options = null, int $dept = 512): void
    {
        $this->setUpJsonResponse(array(
            'success' => false,
            'message' => $message,
            'code' => $code,
            'errors' => $errors
        ), array(), $options, $dept);
    }

    /**
     * Add header to response
     * @param string $value
     * @return static
     */
    public function header(string $value): self
    {
        header($value);

        return $this;
    }

    /**
     * Add multiple headers to response
     * @param array $headers
     * @return static
     */
    public function headers(array $headers): self
    {
        foreach ($headers as $header) {
            $this->header($header);
        }

        return $this;
    }

}
<?php

namespace Pecee\Http;

use Pecee\Http\Exceptions\MalformedUrlException;

class Url
{
    private $originalUrl;
    private $data = [
        'scheme'   => null,
        'host'     => null,
        'port'     => null,
        'user'     => null,
        'pass'     => null,
        'path'     => null,
        'query'    => null,
        'fragment' => null,
    ];

    /**
     * Url constructor.
     * @param string $url
     * @throws MalformedUrlException
     */
    public function __construct($url)
    {
        $this->originalUrl = $url;
        $this->data = $this->parseUrl($url) + $this->data;

        if (isset($this->data['path']) === true && $this->data['path'] !== '/') {
            $this->data['path'] = rtrim($this->data['path'], '/') . '/';
        }

    }

    /**
     * Check if url is using a secure protocol like https
     * @return bool
     */
    public function isSecure(): bool
    {
        return (strtolower($this->getScheme()) === 'https');
    }

    /**
     * Checks if url is relative
     * @return bool
     */
    public function isRelative(): bool
    {
        return ($this->getHost() === null);
    }

    /**
     * Get url scheme
     * @return string|null
     */
    public function getScheme(): ?string
    {
        return $this->data['scheme'];
    }

    /**
     * Get url host
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->data['host'];
    }

    /**
     * Get url port
     * @return int|null
     */
    public function getPort(): ?int
    {
        return ($this->data['port'] !== null) ? (int)$this->data['port'] : null;
    }

    /**
     * Parse username from url
     * @return string|null
     */
    public function getUserName(): ?string
    {
        return $this->data['user'];
    }

    /**
     * Parse password from url
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->data['pass'];
    }

    /**
     * Get path from url
     * @return string
     */
    public function getPath(): ?string
    {
        return $this->data['path'];
    }

    /**
     * Get querystring from url
     * @return string|null
     */
    public function getQueryString(): ?string
    {
        return $this->data['query'];
    }

    /**
     * Get fragment from url (everything after #)
     * @return string|null
     */
    public function getFragment(): ?string
    {
        return $this->data['fragment'];
    }

    /**
     * @return string
     */
    public function getOriginalUrl(): string
    {
        return $this->originalUrl;
    }

    /**
     * UTF-8 aware parse_url() replacement.
     * @param string $url
     * @param int $component
     * @throws MalformedUrlException
     * @return array
     */
    public function parseUrl($url, $component = -1): array
    {
        $encodedUrl = preg_replace_callback(
            '/[^:\/@?&=#]+/u',
            function ($matches) {
                return urlencode($matches[0]);
            },
            $url
        );

        $parts = parse_url($encodedUrl, $component);

        if ($parts === false) {
            throw new MalformedUrlException('Malformed URL: ' . $url);
        }

        return array_map('urldecode', $parts);
    }

    public function contains($value): bool
    {
        return (stripos($this->getOriginalUrl(), $value) === false);
    }

    /**
     * Returns data array with information about the url
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function __toString()
    {
        return $this->getOriginalUrl();
    }

}
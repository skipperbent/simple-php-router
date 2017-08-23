<?php

namespace Pecee\Http;

class Uri
{
    private $originalUrl;
    private $data = [
        'scheme',
        'host',
        'port',
        'user',
        'pass',
        'path',
        'query',
        'fragment',
    ];

    public function __construct($url)
    {
        $this->originalUrl = $url;
        $this->data = array_merge($this->data, $this->parseUrl($url));
    }

    /**
     * Check if url is using a secure protocol like https
     * @return bool
     */
    public function isSecure()
    {
        return (strtolower($this->getScheme()) === 'https');
    }

    /**
     * Checks if url is relative
     * @return bool
     */
    public function isRelative()
    {
        return ($this->getHost() === null);
    }

    /**
     * Get url scheme
     * @return string|null
     */
    public function getScheme()
    {
        return $this->data['scheme'];
    }

    /**
     * Get url host
     * @return string|null
     */
    public function getHost()
    {
        return $this->data['host'];
    }

    /**
     * Get url port
     * @return int|null
     */
    public function getPort()
    {
        return ($this->data['port'] !== null) ? (int)$this->data['port'] : null;
    }

    /**
     * Parse username from url
     * @return string|null
     */
    public function getUserName()
    {
        return $this->data['user'];
    }

    /**
     * Parse password from url
     * @return string|null
     */
    public function getPassword()
    {
        return $this->data['pass'];
    }

    /**
     * Get path from url
     * @return string
     */
    public function getPath()
    {
        return $this->data['path'];
    }

    /**
     * Get querystring from url
     * @return string|null
     */
    public function getQueryString()
    {
        return $this->data['query'];
    }

    /**
     * Get fragment from url (everything after #)
     * @return string|null
     */
    public function getFragment()
    {
        return $this->data['fragment'];
    }

    /**
     * @return string
     */
    public function getOriginalUrl()
    {
        return $this->originalUrl;
    }

    /**
     * UTF-8 aware parse_url() replacement.
     * @throws \InvalidArgumentException
     * @return array
     */
    public function parseUrl($url)
    {
        $enc_url = preg_replace_callback(
            '%[^:/@?&=#]+%u',
            function ($matches) {
                return urlencode($matches[0]);
            },
            $url
        );

        $parts = parse_url($enc_url);

        if ($parts === false) {
            throw new \InvalidArgumentException('Malformed URL: ' . $url);
        }

        foreach ((array)$parts as $name => $value) {
            $parts[$name] = urldecode($value);
        }

        return $parts;
    }

    /**
     * Returns data array with information about the url
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function __toString()
    {
        return $this->getOriginalUrl();
    }

}
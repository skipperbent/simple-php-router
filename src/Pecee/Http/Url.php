<?php

namespace Pecee\Http;

use JsonSerializable;
use Pecee\Http\Exceptions\MalformedUrlException;

/**
 * Class Url
 * @package Pecee\Http
 */
class Url implements JsonSerializable
{
    private $originalUrl;
    private $scheme;
    private $host;
    private $port;
    private $username;
    private $password;
    private $path;
    private $params = [];
    private $fragment;

    /**
     * Url constructor.
     * @param null|string $url
     * @throws MalformedUrlException
     */
    public function __construct(?string $url)
    {
        $this->originalUrl = $url;

        if ($url !== null && $url !== '/') {
            $data = $this->parseUrl($url);

            $this->scheme = $data['scheme'] ?? null;
            $this->host = $data['host'] ?? null;
            $this->port = $data['port'] ?? null;
            $this->username = $data['user'] ?? null;
            $this->password = $data['pass'] ?? null;

            if (isset($data['path']) === true) {
                $this->setPath($data['path']);
            }

            $this->fragment = $data['fragment'] ?? null;

            if (isset($data['query']) === true) {
                $this->setQueryString($data['query']);
            }
        }
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return (strtolower($this->getScheme()) === 'https');
    }

    /**
     * @return bool
     */
    public function isRelative(): bool
    {
        return ($this->getHost() === null);
    }

    /**
     * @return null|string
     */
    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     * @return Url
     */
    public function setScheme(string $scheme): self
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return Url
     */
    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return ($this->port !== null) ? (int)$this->port : null;
    }

    /**
     * @param int $port
     * @return Url
     */
    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return Url
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return Url
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getPath(): ?string
    {
        return $this->path ?? '/';
    }

    /**
     * @param string $path
     * @return Url
     */
    public function setPath(string $path): self
    {
        $this->path = rtrim($path, '/') . '/';

        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array $params
     * @return Url
     */
    public function mergeParams(array $params): self
    {
        return $this->setParams(array_merge($this->getParams(), $params));
    }

    /**
     * @param array $params
     * @return Url
     */
    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @param string $queryString
     * @return Url
     */
    public function setQueryString(string $queryString): self
    {
        $params = [];

        if (parse_str($queryString, $params) !== false) {
            return $this->setParams($params);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getQueryString(): string
    {
        return static::arrayToParams($this->getParams());
    }

    /**
     * @return null|string
     */
    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    /**
     * @param string $fragment
     * @return Url
     */
    public function setFragment(string $fragment): self
    {
        $this->fragment = $fragment;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalUrl(): string
    {
        return $this->originalUrl;
    }

    /**
     * @param string $value
     * @return int
     */
    public function indexOf(string $value): int
    {
        $index = stripos($this->getOriginalUrl(), $value);

        return ($index === false) ? -1 : $index;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function contains(string $value): bool
    {
        return (stripos($this->getOriginalUrl(), $value) !== false);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->getParams());
    }

    /**
     * @param mixed ...$names
     * @return Url
     */
    public function removeParams(...$names): self
    {
        $params = array_diff_key($this->getParams(), array_flip($names));
        $this->setParams($params);

        return $this;
    }

    /**
     * @param string $name
     * @return Url
     */
    public function removeParam(string $name): self
    {
        $params = $this->getParams();
        unset($params[$name]);
        $this->setParams($params);

        return $this;
    }

    /**
     * @param string $name
     * @param null|string $defaultValue
     * @return null|string
     */
    public function getParam(string $name, ?string $defaultValue = null): ?string
    {
        return isset($this->getParams()[$name]) ?? $defaultValue;
    }

    /**
     * @param string $url
     * @param int $component
     * @return array
     * @throws MalformedUrlException
     */
    public function parseUrl(string $url, int $component = -1): array
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
            throw new MalformedUrlException(sprintf('Failed to parse url: "%s"', $url));
        }

        return array_map('urldecode', $parts);
    }

    /**
     * @param array $getParams
     * @param bool $includeEmpty
     * @return string
     */
    public static function arrayToParams(array $getParams = [], bool $includeEmpty = true): string
    {
        if (\count($getParams) !== 0) {

            if ($includeEmpty === false) {
                $getParams = array_filter($getParams, function ($item) {
                    return (trim($item) !== '');
                });
            }

            return http_build_query($getParams);
        }

        return '';
    }

    /**
     * @return string
     */
    public function getRelativeUrl(): string
    {
        $params = $this->getQueryString();

        $path = $this->path ?? '';
        $query = $params !== '' ? '?' . $params : '';
        $fragment = $this->fragment !== null ? '#' . $this->fragment : '';

        return $path . $query . $fragment;
    }

    /**
     * @return string
     */
    public function getAbsoluteUrl(): string
    {
        $scheme = $this->scheme !== null ? $this->scheme . '://' : '';
        $host = $this->host ?? '';
        $port = $this->port !== null ? ':' . $this->port : '';
        $user = $this->username ?? '';
        $pass = $this->password !== null ? ':' . $this->password : '';
        $pass = ($user || $pass) ? $pass . '@' : '';

        return $scheme . $user . $pass . $host . $port . $this->getRelativeUrl();
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->getRelativeUrl();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getRelativeUrl();
    }

}
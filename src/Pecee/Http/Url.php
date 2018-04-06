<?php

namespace Pecee\Http;

use Pecee\Http\Exceptions\MalformedUrlException;

class Url implements \JsonSerializable
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
     *
     * @param string $url
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
     * Check if url is using a secure protocol like https
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return (strtolower($this->getScheme()) === 'https');
    }

    /**
     * Checks if url is relative
     *
     * @return bool
     */
    public function isRelative(): bool
    {
        return ($this->getHost() === null);
    }

    /**
     * Get url scheme
     *
     * @return string|null
     */
    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    /**
     * Set the scheme of the url
     *
     * @param string $scheme
     * @return static
     */
    public function setScheme(string $scheme): self
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * Get url host
     *
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * Set the host of the url
     *
     * @param string $host
     * @return static
     */
    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get url port
     *
     * @return int|null
     */
    public function getPort(): ?int
    {
        return ($this->port !== null) ? (int)$this->port : null;
    }

    /**
     * Set the port of the url
     *
     * @param int $port
     * @return static
     */
    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Parse username from url
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Set the username of the url
     *
     * @param string $username
     * @return static
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Parse password from url
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set the url password
     *
     * @param string $password
     * @return static
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get path from url
     * @return string
     */
    public function getPath(): ?string
    {
        return $this->path ?? '/';
    }

    /**
     * Set the url path
     *
     * @param string $path
     * @return static
     */
    public function setPath(string $path): self
    {
        $this->path = rtrim($path, '/') . '/';

        return $this;
    }

    /**
     * Get query-string from url
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Merge parameters array
     *
     * @param array $params
     * @return static
     */
    public function mergeParams(array $params): self
    {
        return $this->setParams(array_merge($this->getParams(), $params));
    }

    /**
     * Set the url params
     *
     * @param array $params
     * @return static
     */
    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Set raw query-string parameters as string
     *
     * @param string $queryString
     * @return static
     */
    public function setQueryString(string $queryString): self
    {
        $params = [];

        if(parse_str($queryString, $params) !== false) {
            return $this->setParams($params);
        }

        return $this;
    }

    /**
     * Get query-string params as string
     *
     * @return string
     */
    public function getQueryString(): string
    {
        return static::arrayToParams($this->getParams());
    }

    /**
     * Get fragment from url (everything after #)
     *
     * @return string|null
     */
    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    /**
     * Set url fragment
     *
     * @param string $fragment
     * @return static
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
     * Get position of value.
     * Returns -1 on failure.
     *
     * @param string $value
     * @return int
     */
    public function indexOf(string $value): int
    {
        $index = stripos($this->getOriginalUrl(), $value);

        return ($index === false) ? -1 : $index;
    }

    /**
     * Check if url contains value.
     *
     * @param string $value
     * @return bool
     */
    public function contains(string $value): bool
    {
        return (stripos($this->getOriginalUrl(), $value) !== false);
    }

    /**
     * Check if url contains parameter/query string.
     *
     * @param string $name
     * @return bool
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->getParams());
    }

    /**
     * Removes multiple parameters from the query-string
     *
     * @param array ...$names
     * @return static
     */
    public function removeParams(...$names): self
    {
        $params = array_diff_key($this->getParams(), array_flip($names));
        $this->setParams($params);

        return $this;
    }

    /**
     * Removes parameter from the query-string
     *
     * @param string $name
     * @return static
     */
    public function removeParam(string $name): self
    {
        $params = $this->getParams();
        unset($params[$name]);
        $this->setParams($params);

        return $this;
    }

    /**
     * Get parameter by name.
     * Returns parameter value or default value.
     *
     * @param string $name
     * @param string|null $defaultValue
     * @return string|null
     */
    public function getParam(string $name, ?string $defaultValue = null): ?string
    {
        return isset($this->getParams()[$name]) ?? $defaultValue;
    }

    /**
     * UTF-8 aware parse_url() replacement.
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
     * Convert array to query-string params
     *
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
     * Returns the relative url
     *
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
     * Returns the absolute url
     *
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
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): string
    {
        return $this->getRelativeUrl();
    }

    public function __toString(): string
    {
        return $this->getRelativeUrl();
    }

}
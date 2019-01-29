<?php

namespace Pecee\Http\Security;

use Pecee\Http\Security\Exceptions\SecurityException;

/**
 * Class CookieTokenProvider
 *
 * @package Pecee\Http\Security
 */
class CookieTokenProvider implements ITokenProvider
{
    public const CSRF_KEY = 'CSRF-TOKEN';

    protected $token;
    protected $cookieTimeoutMinutes = 120;

    /**
     * CookieTokenProvider constructor.
     * @throws SecurityException
     */
    public function __construct()
    {
        $this->token = $this->getToken();

        if ($this->token === null) {
            $this->token = $this->generateToken();
        }
    }

    /**
     * @return string
     * @throws SecurityException
     */
    public function generateToken(): string
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            throw new SecurityException($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @param string $token
     * @return bool
     */
    public function validate(string $token): bool
    {
        if ($this->getToken() !== null) {
            return hash_equals($token, $this->getToken());
        }

        return false;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
        setcookie(static::CSRF_KEY, $token, (time() + 60) * $this->cookieTimeoutMinutes, '/', ini_get('session.cookie_domain'), ini_get('session.cookie_secure'), ini_get('session.cookie_httponly'));
    }

    /**
     * @param null|string $defaultValue
     * @return null|string
     */
    public function getToken(?string $defaultValue = null): ?string
    {
        $this->token = ($this->hasToken() === true) ? $_COOKIE[static::CSRF_KEY] : null;

        return $this->token ?? $defaultValue;
    }

    public function refresh(): void
    {
        if ($this->token !== null) {
            $this->setToken($this->token);
        }
    }

    /**
     * @return bool
     */
    public function hasToken(): bool
    {
        return isset($_COOKIE[static::CSRF_KEY]);
    }

    /**
     * @return int
     */
    public function getCookieTimeoutMinutes(): int
    {
        return $this->cookieTimeoutMinutes;
    }

    /**
     * @param int $minutes
     */
    public function setCookieTimeoutMinutes(int $minutes): void
    {
        $this->cookieTimeoutMinutes = $minutes;
    }
}
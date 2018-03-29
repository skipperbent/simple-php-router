<?php

namespace Pecee\Http\Security;

use Pecee\Http\Security\Exceptions\SecurityException;

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
     * Generate random identifier for CSRF token
     *
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
     * Validate valid CSRF token
     *
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
     * Set csrf token cookie
     * Overwrite this method to save the token to another storage like session etc.
     *
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
        setcookie(static::CSRF_KEY, $token, (int)((time() + 60) * $this->cookieTimeoutMinutes), '/', ini_get('session.cookie_domain'), ini_get('session.cookie_secure'), ini_get('session.cookie_httponly'));
    }

    /**
     * Get csrf token
     * @param string|null $defaultValue
     * @return string|null
     */
    public function getToken(?string $defaultValue = null): ?string
    {
        $this->token = ($this->hasToken() === true) ? $_COOKIE[static::CSRF_KEY] : null;

        return $this->token ?? $defaultValue;
    }

    /**
     * Refresh existing token
     */
    public function refresh(): void
    {
        if ($this->token !== null) {
            $this->setToken($this->token);
        }
    }

    /**
     * Returns whether the csrf token has been defined
     * @return bool
     */
    public function hasToken(): bool
    {
        return isset($_COOKIE[static::CSRF_KEY]);
    }

    /**
     * Get timeout for cookie in minutes
     * @return int
     */
    public function getCookieTimeoutMinutes(): int
    {
        return $this->cookieTimeoutMinutes;
    }

    /**
     * Set cookie timeout in minutes
     * @param int $minutes
     */
    public function setCookieTimeoutMinutes(int $minutes): void
    {
        $this->cookieTimeoutMinutes = $minutes;
    }

}
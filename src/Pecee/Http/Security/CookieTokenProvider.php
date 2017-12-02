<?php

namespace Pecee\Http\Security;

class CookieTokenProvider implements ITokenProvider
{
    const CSRF_KEY = 'CSRF-TOKEN';

    protected $token;
    protected $cookieTimeoutMinutes = 120;

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
     * @throws \RuntimeException|\Exception
     * @return string
     */
    public function generateToken()
    {
        if (function_exists('random_bytes') === true) {
            return bin2hex(random_bytes(32));
        }

        $isSourceStrong = false;

        $random = openssl_random_pseudo_bytes(32, $isSourceStrong);
        if ($isSourceStrong === false || $random === false) {
            throw new \RuntimeException('IV generation failed');
        }

        return $random;
    }

    /**
     * Validate valid CSRF token
     *
     * @param string $token
     * @return bool
     */
    public function validate($token)
    {
        if ($token !== null && $this->getToken() !== null) {
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
    public function setToken($token)
    {
        $this->token = $token;
        setcookie(static::CSRF_KEY, $token, time() + 60 * $this->cookieTimeoutMinutes, '/');
    }

    /**
     * Get csrf token
     * @param string|null $defaultValue
     * @return string|null
     */
    public function getToken($defaultValue = null)
    {
        $this->token = ($this->hasToken() === true) ? $_COOKIE[static::CSRF_KEY] : null;

        return ($this->token !== null) ? $this->token : $defaultValue;
    }

    /**
     * Refresh existing token
     */
    public function refresh()
    {
        if ($this->token !== null) {
            $this->setToken($this->token);
        }
    }

    /**
     * Returns whether the csrf token has been defined
     * @return bool
     */
    public function hasToken()
    {
        return isset($_COOKIE[static::CSRF_KEY]);
    }

    /**
     * Get timeout for cookie in minutes
     * @return int
     */
    public function getCookieTimeoutMinutes()
    {
        return $this->cookieTimeoutMinutes;
    }

    /**
     * Set cookie timeout in minutes
     * @param $minutes
     */
    public function setCookieTimeoutMinutes($minutes)
    {
        $this->cookieTimeoutMinutes = $minutes;
    }

}
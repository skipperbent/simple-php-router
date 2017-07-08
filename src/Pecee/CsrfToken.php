<?php
namespace Pecee;

class CsrfToken
{
    const CSRF_KEY = 'CSRF-TOKEN';

    protected $token;

    /**
     * Generate random identifier for CSRF token
     *
     * @throws \RuntimeException
     * @return string
     */
    public static function generateToken()
    {
        if (function_exists('random_bytes')) {
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
     *
     * @param $token
     */
    public function setToken($token)
    {
        setcookie(static::CSRF_KEY, $token, time() + 60 * 120, '/');
    }

    /**
     * Get csrf token
     * @return string|null
     */
    public function getToken()
    {
        if ($this->hasToken() === true) {
            return $_COOKIE[static::CSRF_KEY];
        }

        return null;
    }

    /**
     * Returns whether the csrf token has been defined
     * @return bool
     */
    public function hasToken()
    {
        return isset($_COOKIE[static::CSRF_KEY]);
    }

}
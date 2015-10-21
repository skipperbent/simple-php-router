<?php
namespace Pecee;

class CsrfToken {

    const CSRF_KEY = 'XSRF-TOKEN';

    protected $token;

    public function __construct() {
        if($this->getToken() === null) {
            $this->setToken($this->generateToken());
        }
    }

    /**
     * Generate random identifier for CSRF token
     * @return string
     */
    public static function generateToken() {
        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
        }
        return bin2hex(openssl_random_pseudo_bytes(32));
    }

    /**
     * Validate valid CSRF token
     *
     * @param string $token
     * @return bool
     */
    public function validate($token) {
        if($token !== null && $this->getToken() !== null) {
            return hash_equals($token, $this->getToken());
        }
        return false;
    }

    /**
     * Set csrf token cookie
     *
     * @param $token
     */
    public function setToken($token) {
        setcookie(self::CSRF_KEY, $token, time() + 60 * 120, '/');
    }

    /**
     * Get csrf token
     * @return string|null
     */
    public function getToken(){
        if(isset($_COOKIE[self::CSRF_KEY])) {
            return $_COOKIE[self::CSRF_KEY];
        }
        return null;
    }

}
<?php
namespace Pecee;

class CsrfToken {

    const CSRF_KEY = 'csrf';

    protected $token;

    public function __construct() {
        $this->lastToken = isset($_SESSION[self::CSRF_KEY]) ? $_SESSION[self::CSRF_KEY] : null;
        $this->currentToken = $this->generate();

        $_COOKIE[self::CSRF_KEY] = $this->currentToken;
    }

    /**
     * Generate random identifier for CSRF token
     * @return string
     */
    public static function generate() {
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
        return hash_equals($token, $this->getCurrentToken());
    }

    /**
     * @return string|null
     */
    public function getToken(){
        if(isset($_COOKIE[self::CSRF_KEY])) {
            return $_COOKIE[self::CSRF_KEY];
        }
        return null;
    }

}
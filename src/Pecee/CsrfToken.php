<?php
namespace Pecee;

class CsrfToken {

    const CSRF_KEY = 'csrf_token';

    protected static $instance;

    protected $lastToken;
    protected $currentToken;

    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->lastToken = isset($_SESSION[self::CSRF_KEY]) ? $_SESSION[self::CSRF_KEY] : null;
        $this->currentToken = $this->generate();

        // Initialise session, if it hasn't been initialised.
        if(!isset($_SESSION)) {
            session_start();
        }

        $_SESSION['csrf_token'] = $this->currentToken;
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
        return hash_equals($token, $_SESSION[self::CSRF_KEY]);
    }

    /**
     * @return string|null
     */
    public function getLastToken(){
        return $this->lastToken;
    }

    /**
     * @param string|null $lastToken
     */
    public function setLastToken($lastToken){
        $this->lastToken = $lastToken;
    }

    /**
     * @return string|null
     */
    public function getCurrentToken(){
        return $this->currentToken;
    }

}
<?php
namespace Pecee\Http\Middleware;

use Pecee\CsrfToken;
use Pecee\Http\Middleware\Exceptions\TokenMismatchException;
use Pecee\Http\Request;

class BaseCsrfVerifier implements IMiddleware
{
    const POST_KEY = 'csrf-token';
    const HEADER_KEY = 'X-CSRF-TOKEN';

    protected $except;
    protected $csrfToken;
    protected $token;

    public function __construct()
    {
        $this->csrfToken = new CsrfToken();

        // Generate or get the CSRF-Token from Cookie.
        $this->token = ($this->hasToken() === false) ? $this->generateToken() : $this->csrfToken->getToken();
    }

    /**
     * Check if the url matches the urls in the except property
     * @param Request $request
     * @return bool
     */
    protected function skip(Request $request)
    {
        if ($this->except === null || is_array($this->except) === false) {
            return false;
        }

        $max = count($this->except) - 1;

        for ($i = $max; $i >= 0; $i--) {
            $url = $this->except[$i];

            $url = rtrim($url, '/');
            if ($url[strlen($url) - 1] === '*') {
                $url = rtrim($url, '*');
                $skip = (stripos($request->getUri(), $url) === 0);
            } else {
                $skip = ($url === rtrim($request->getUri(), '/'));
            }

            if ($skip === true) {
                return true;
            }
        }

        return false;
    }

    public function handle(Request $request)
    {

        if ($this->skip($request) === false && in_array($request->getMethod(), ['post', 'put', 'delete'], false) === true) {

            $token = $request->getInput()->get(static::POST_KEY, null, 'post');

            // If the token is not posted, check headers for valid x-csrf-token
            if ($token === null) {
                $token = $request->getHeader(static::HEADER_KEY);
            }

            if ($this->csrfToken->validate($token) === false) {
                throw new TokenMismatchException('Invalid csrf-token.');
            }

        }

    }

    public function generateToken()
    {
        $token = CsrfToken::generateToken();
        $this->csrfToken->setToken($token);

        return $token;
    }

    public function hasToken()
    {
        if ($this->token !== null) {
            return true;
        }

        return $this->csrfToken->hasToken();
    }

    public function getToken()
    {
        return $this->token;
    }

}
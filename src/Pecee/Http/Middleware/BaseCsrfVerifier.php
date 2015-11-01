<?php
namespace Pecee\Http\Middleware;

use Pecee\CsrfToken;
use Pecee\Exception\TokenMismatchException;
use Pecee\Http\Request;

class BaseCsrfVerifier implements IMiddleware {

    const POST_KEY = 'csrf-token';
    const HEADER_KEY = 'X-CSRF-TOKEN';

    protected $except;
    protected $csrfToken;


    public function __construct() {
        $this->csrfToken = new CsrfToken();
    }

    /**
     * Check if the url matches the urls in the except property
     * @param Request $request
     * @return bool
     */
    protected function skip(Request $request) {

        if($this->except === null || !is_array($this->except)) {
            return false;
        }

        foreach($this->except as $url) {
            $url = rtrim($url, '/');
            if($url[strlen($url)-1] === '*') {
                $url = rtrim($url, '*');
                $skip = (stripos($request->getUri(), $url) === 0);
            } else {
                $skip = ($url === rtrim($request->getUri(), '/'));
            }

            if($skip) {
                return true;
            }
        }

        return false;
    }

    public function handle(Request $request) {

        if($request->getMethod() != 'get' && !$this->skip($request)) {

            $token = (isset($_POST[self::POST_KEY])) ? $_POST[self::POST_KEY] : null;

            // If the token is not posted, check headers for valid x-csrf-token
            if($token === null) {
                $token = $request->getHeader(self::HEADER_KEY);
            }

            if( !$this->csrfToken->validate( $token ) ) {
                throw new TokenMismatchException('Invalid csrf-token.');
            }

        }

    }

}
<?php

namespace Pecee\Http\Middleware;

use Pecee\CsrfToken;
use Pecee\Http\Request;
use Pecee\SimpleRouter\RouterException;

class BaseCsrfVerifier extends Middleware {

    const POST_KEY = 'csrf-token';
    const HEADER_KEY = 'X-CSRF-TOKEN';

    public function handle(Request $request) {

        if($request->getMethod() != 'get') {

            $token = (isset($_POST[self::POST_KEY])) ? $_POST[self::POST_KEY] : null;

            // If the token is not posted, check headers for valid x-csrf-token
            if($token === null) {
                $token = $request->getHeader(self::HEADER_KEY);
            }

            $tokenValidator = new CsrfToken();
            if( !$tokenValidator->validate( $token ) ) {
                throw new RouterException('Invalid csrf-token.');
            }

        }

    }
}
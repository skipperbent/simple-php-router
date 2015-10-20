<?php

namespace Pecee\Http;

class Response {

    /**
     * Set the http status code
     *
     * @param int $code
     * @return self $this
     */
    public function httpCode($code) {
        http_response_code($code);
        return $this;
    }

    /**
     * Redirect the response
     *
     * @param string $url
     */
    public function redirect($url) {
        header('location: ' . $url);
        die();
    }

    public function refresh() {
        $this->redirect(url());
    }

}
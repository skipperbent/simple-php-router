<?php
use Pecee\SimpleRouter\SimpleRouter;

function url($controller, $parameters = null, $getParams = null) {
    SimpleRouter::getRoute($controller, $parameters, $getParams);
}

/**
 * Get current csrf-token
 * @return null|string
 */
function csrf_token() {
    $token = new \Pecee\CsrfToken();
    return $token->getToken();
}

/**
 * Get request object
 * @return \Pecee\Http\Request
 */
function request() {
    return SimpleRouter::request();
}

/**
 * Get response object
 * @return \Pecee\Http\Response
 */
function response() {
    return SimpleRouter::response();
}

/**
 * Get input class
 * @return \Pecee\Http\Input\Input
 */
function input() {
    return SimpleRouter::request()->getInput();
}
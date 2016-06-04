<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';

class MiddlewareTest extends PHPUnit_Framework_TestCase  {

    public function __construct() {
        // Initial setup
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/my/test/url';
        $_SERVER['REQUEST_METHOD'] = 'get';
    }

    public function testMiddlewareFound() {

        \Pecee\Http\Request::getInstance()->setMethod('get');

        \Pecee\SimpleRouter\RouterBase::reset();

        \Pecee\SimpleRouter\SimpleRouter::get('/my/test/url', 'DummyController@start', ['middleware' => 'DummyMiddleware']);

        $found = false;

        try {
            \Pecee\SimpleRouter\SimpleRouter::start();
        }catch(Exception $e) {
            $found = ($e instanceof MiddlewareLoadedException);
        }

        $this->assertTrue($found);

    }

}
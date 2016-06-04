<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';

class RouterRouteTest extends PHPUnit_Framework_TestCase  {

    public function __construct() {
        // Initial setup
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/my/test/url';
        $_SERVER['REQUEST_METHOD'] = 'get';
    }

    public function testGet() {

        \Pecee\SimpleRouter\RouterBase::reset();

        \Pecee\Http\Request::getInstance()->setMethod('get');

        \Pecee\SimpleRouter\SimpleRouter::get('/my/test/url', 'DummyController@start');
        \Pecee\SimpleRouter\SimpleRouter::start();
    }

    public function testPost() {
        \Pecee\Http\Request::getInstance()->setMethod('post');

        \Pecee\SimpleRouter\RouterBase::reset();

        \Pecee\SimpleRouter\SimpleRouter::post('/my/test/url', 'DummyController@start');
        \Pecee\SimpleRouter\SimpleRouter::start();
    }

    public function testPut() {
        \Pecee\Http\Request::getInstance()->setMethod('put');

        \Pecee\SimpleRouter\RouterBase::reset();

        \Pecee\SimpleRouter\SimpleRouter::put('/my/test/url', 'DummyController@start');
        \Pecee\SimpleRouter\SimpleRouter::start();
    }

    public function testDelete() {
        \Pecee\Http\Request::getInstance()->setMethod('delete');

        \Pecee\SimpleRouter\RouterBase::reset();

        \Pecee\SimpleRouter\SimpleRouter::delete('/my/test/url', 'DummyController@start');
        \Pecee\SimpleRouter\SimpleRouter::start();

    }

    public function testMethodNotAllowed() {

        \Pecee\SimpleRouter\RouterBase::reset();

        \Pecee\Http\Request::getInstance()->setMethod('post');

        \Pecee\SimpleRouter\SimpleRouter::get('/my/test/url', 'DummyController@start');

        try {
            \Pecee\SimpleRouter\SimpleRouter::start();
        } catch(\Exception $e) {
            $this->assertEquals(403, $e->getCode());
        }

    }

    public function testSimpleParam() {

        \Pecee\SimpleRouter\RouterBase::reset();

        \Pecee\Http\Request::getInstance()->setMethod('get');
        \Pecee\Http\Request::getInstance()->setUri('/test-param1');

        \Pecee\SimpleRouter\SimpleRouter::get('/test-{param1}', 'DummyController@param');
        \Pecee\SimpleRouter\SimpleRouter::start();

    }

    public function testMultiParam() {

        \Pecee\SimpleRouter\RouterBase::reset();

        \Pecee\Http\Request::getInstance()->setMethod('get');
        \Pecee\Http\Request::getInstance()->setUri('/test-param1-param2');

        \Pecee\SimpleRouter\SimpleRouter::get('/test-{param1}-{param2}', 'DummyController@param');
        \Pecee\SimpleRouter\SimpleRouter::start();

    }

    public function testPathParam() {

        \Pecee\SimpleRouter\RouterBase::reset();

        \Pecee\Http\Request::getInstance()->setMethod('get');
        \Pecee\Http\Request::getInstance()->setUri('/test/path/param1');

        \Pecee\SimpleRouter\SimpleRouter::get('/test/path/{param}', 'DummyController@param');
        \Pecee\SimpleRouter\SimpleRouter::start();

    }

}
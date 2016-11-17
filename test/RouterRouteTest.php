<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';

class RouterRouteTest extends PHPUnit_Framework_TestCase  {

    protected $result = false;

    public function testNotFound() {
        \Pecee\SimpleRouter\RouterBase::getInstance()->reset();
        \Pecee\SimpleRouter\SimpleRouter::request()->setMethod('get');
        \Pecee\SimpleRouter\SimpleRouter::request()->setUri('/test-param1-param2');

        \Pecee\SimpleRouter\SimpleRouter::group(['exceptionHandler' => 'ExceptionHandler'], function() {
            \Pecee\SimpleRouter\SimpleRouter::get('/non-existing-path', 'DummyController@start');
        });

        $found = false;

        try {
            \Pecee\SimpleRouter\SimpleRouter::start();
        }catch(\Exception $e) {
            $found = ($e instanceof \Pecee\Exception\RouterException && $e->getCode() == 404);
        }

        $this->assertTrue($found);

    }

    public function testGet() {
        \Pecee\SimpleRouter\RouterBase::getInstance()->reset();
        \Pecee\SimpleRouter\SimpleRouter::request()->setUri('/my/test/url');
        \Pecee\SimpleRouter\SimpleRouter::request()->setMethod('get');

        \Pecee\SimpleRouter\SimpleRouter::get('/my/test/url', 'DummyController@start');
        \Pecee\SimpleRouter\SimpleRouter::start();
    }

    public function testPost() {
        \Pecee\SimpleRouter\RouterBase::getInstance()->reset();
        \Pecee\SimpleRouter\SimpleRouter::request()->setUri('/my/test/url');
        \Pecee\SimpleRouter\SimpleRouter::request()->setMethod('post');

        \Pecee\SimpleRouter\SimpleRouter::post('/my/test/url', 'DummyController@start');
        \Pecee\SimpleRouter\SimpleRouter::start();
    }

    public function testPut() {
        \Pecee\SimpleRouter\RouterBase::getInstance()->reset();
        \Pecee\SimpleRouter\SimpleRouter::request()->setUri('/my/test/url');
        \Pecee\SimpleRouter\SimpleRouter::request()->setMethod('put');

        \Pecee\SimpleRouter\SimpleRouter::put('/my/test/url', 'DummyController@start');
        \Pecee\SimpleRouter\SimpleRouter::start();
    }

    public function testDelete() {
        \Pecee\SimpleRouter\RouterBase::getInstance()->reset();
        \Pecee\SimpleRouter\SimpleRouter::request()->setUri('/my/test/url');
        \Pecee\SimpleRouter\SimpleRouter::request()->setMethod('delete');

        \Pecee\SimpleRouter\SimpleRouter::delete('/my/test/url', 'DummyController@start');
        \Pecee\SimpleRouter\SimpleRouter::start();

    }

    public function testMethodNotAllowed() {
        \Pecee\SimpleRouter\RouterBase::getInstance()->reset();
        \Pecee\SimpleRouter\SimpleRouter::request()->setUri('/my/test/url');
        \Pecee\SimpleRouter\SimpleRouter::request()->setMethod('post');

        \Pecee\SimpleRouter\SimpleRouter::get('/my/test/url', 'DummyController@start');

        try {
            \Pecee\SimpleRouter\SimpleRouter::start();
        } catch(\Exception $e) {
            $this->assertEquals(403, $e->getCode());
        }

    }

    public function testSimpleParam() {

        \Pecee\SimpleRouter\RouterBase::getInstance()->reset();
        \Pecee\SimpleRouter\SimpleRouter::request()->setMethod('get');
        \Pecee\SimpleRouter\SimpleRouter::request()->setUri('/test-param1');

        \Pecee\SimpleRouter\SimpleRouter::get('/test-{param1}', 'DummyController@param');
        \Pecee\SimpleRouter\SimpleRouter::start();

    }

    public function testMultiParam() {

        \Pecee\SimpleRouter\RouterBase::getInstance()->reset();
        \Pecee\SimpleRouter\SimpleRouter::request()->setMethod('get');
        \Pecee\SimpleRouter\SimpleRouter::request()->setUri('/test-param1-param2');

        \Pecee\SimpleRouter\SimpleRouter::get('/test-{param1}-{param2}', 'DummyController@param');
        \Pecee\SimpleRouter\SimpleRouter::start();

    }

    public function testPathParamRegex() {

        \Pecee\SimpleRouter\RouterBase::getInstance()->reset();
        \Pecee\SimpleRouter\SimpleRouter::request()->setMethod('get');
        \Pecee\SimpleRouter\SimpleRouter::request()->setUri('/test/path/123123');

        \Pecee\SimpleRouter\SimpleRouter::get('/test/path/{myParam}', 'DummyController@param', ['where' => ['myParam' => '([0-9]+)']]);
        \Pecee\SimpleRouter\SimpleRouter::start();

    }

    public function testDomainRoute() {

        \Pecee\SimpleRouter\RouterBase::getInstance()->reset();
        \Pecee\SimpleRouter\SimpleRouter::request()->setMethod('get');
        \Pecee\SimpleRouter\SimpleRouter::request()->setUri('/test');
        \Pecee\SimpleRouter\SimpleRouter::request()->setHost('hello.world.com');

        $this->result = false;

        \Pecee\SimpleRouter\SimpleRouter::group(['domain' => '{subdomain}.world.com'], function() {
            \Pecee\SimpleRouter\SimpleRouter::get('test', function($subdomain = null) {
                $this->result = ($subdomain === 'hello');
            });
        });

        \Pecee\SimpleRouter\SimpleRouter::start();

        $this->assertTrue($this->result);

    }

}
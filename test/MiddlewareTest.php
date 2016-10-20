<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';

class MiddlewareTest extends PHPUnit_Framework_TestCase  {

    public function testMiddlewareFound() {
        \Pecee\SimpleRouter\RouterBase::reset();

        \Pecee\Http\Request::getInstance()->setMethod('get');
        \Pecee\Http\Request::getInstance()->setUri('/my/test/url');

        \Pecee\SimpleRouter\SimpleRouter::group(['exceptionHandler' => 'ExceptionHandler'], function() {
            \Pecee\SimpleRouter\SimpleRouter::get('/my/test/url', 'DummyController@start', ['middleware' => 'DummyMiddleware']);
        });

        $found = false;

        try {
            \Pecee\SimpleRouter\SimpleRouter::start();
        }catch(\Exception $e) {
            $found = ($e instanceof MiddlewareLoadedException);
        }

        $this->assertTrue($found);

    }

}
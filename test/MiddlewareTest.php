<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';

use Pecee\SimpleRouter\SimpleRouter as SimpleRouter;

class MiddlewareTest extends PHPUnit_Framework_TestCase
{
    public function testMiddlewareFound()
    {
        $this->setExpectedException('MiddlewareLoadedException');

        SimpleRouter::router()->reset();
        SimpleRouter::request()->setMethod('get');
        SimpleRouter::request()->setUri('/my/test/url');

        SimpleRouter::group(['exceptionHandler' => 'ExceptionHandler'], function () {
            SimpleRouter::get('/my/test/url', 'DummyController@start', ['middleware' => 'DummyMiddleware']);
        });

        SimpleRouter::start();
    }

    public function testNestedMiddlewareLoad()
    {
        $this->setExpectedException('MiddlewareLoadedException');

        SimpleRouter::router()->reset();
        SimpleRouter::request()->setMethod('get');
        SimpleRouter::request()->setUri('/my/test/url');

        SimpleRouter::group(['exceptionHandler' => 'ExceptionHandler', 'middleware' => 'DummyMiddleware'], function () {
            SimpleRouter::get('/my/test/url', 'DummyController@start');
        });

        SimpleRouter::start();
    }

}
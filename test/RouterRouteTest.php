<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Exceptions/ExceptionHandlerException.php';
require_once 'Dummy/Handler/TestExceptionHandlerFirst.php';
require_once 'Dummy/Handler/TestExceptionHandlerSecond.php';
require_once 'Dummy/Handler/TestExceptionHandlerThird.php';

use Pecee\SimpleRouter\Exceptions\NotFoundHttpException as NotFoundHttpException;
use Pecee\SimpleRouter\SimpleRouter as SimpleRouter;

class RouterRouteTest extends PHPUnit_Framework_TestCase
{
    protected $result = false;

    public function testMultiParam()
    {
        SimpleRouter::router()->reset();
        SimpleRouter::request()->setMethod('get');
        SimpleRouter::request()->setUri('/test-param1-param2');

        SimpleRouter::get('/test-{param1}-{param2}', function($param1, $param2) {

            if($param1 === 'param1' && $param2 === 'param2') {
                $this->result = true;
            }

        });

        SimpleRouter::start();

        $this->assertTrue($this->result);

    }

    /**
     * Redirects to another route through 3 exception handlers.
     *
     * You will see "ExceptionHandler 1 loaded" 2 times. This happen because
     * the exceptionhandler is asking the router to reload.
     *
     * That means that the exceptionhandler is loaded again, but this time
     * the router ignores the same rewrite-route to avoid loop - loads
     * the second which have same behavior and is also ignored before
     * throwing the final Exception in ExceptionHandler 3.
     *
     * So this tests:
     * 1. If ExceptionHandlers loads
     * 2. If ExceptionHandlers load in the correct order
     * 3. If ExceptionHandlers can rewrite the page on error
     * 4. If the router can avoid redirect-loop due to developer has started loop.
     * 5. And finally if we reaches the last exception-handler and that the correct
     *    exception-type is being thrown.
     */
    public function testNotFound()
    {
        $this->setExpectedException('ExceptionHandlerException');

        SimpleRouter::router()->reset();
        SimpleRouter::request()->setMethod('get');
        SimpleRouter::request()->setUri('/test-param1-param2');

        SimpleRouter::group(['exceptionHandler' => ['TestExceptionHandlerFirst', 'TestExceptionHandlerSecond']], function () {

            SimpleRouter::group(['exceptionHandler' => 'TestExceptionHandlerThird'], function () {

                SimpleRouter::get('/non-existing-path', 'DummyController@start');

            });
        });

        SimpleRouter::start();
    }

    public function testGet()
    {
        SimpleRouter::router()->reset();
        SimpleRouter::request()->setUri('/my/test/url');
        SimpleRouter::request()->setMethod('get');

        SimpleRouter::get('/my/test/url', 'DummyController@start');
        SimpleRouter::start();
    }

    public function testPost()
    {
        SimpleRouter::router()->reset();
        SimpleRouter::request()->setUri('/my/test/url');
        SimpleRouter::request()->setMethod('post');

        SimpleRouter::post('/my/test/url', 'DummyController@start');
        SimpleRouter::start();
    }

    public function testPut()
    {
        SimpleRouter::router()->reset();
        SimpleRouter::request()->setUri('/my/test/url');
        SimpleRouter::request()->setMethod('put');

        SimpleRouter::put('/my/test/url', 'DummyController@start');
        SimpleRouter::start();
    }

    public function testDelete()
    {
        SimpleRouter::router()->reset();
        SimpleRouter::request()->setUri('/my/test/url');
        SimpleRouter::request()->setMethod('delete');

        SimpleRouter::delete('/my/test/url', 'DummyController@start');
        SimpleRouter::start();
    }

    public function testMethodNotAllowed()
    {
        SimpleRouter::router()->reset();
        SimpleRouter::request()->setUri('/my/test/url');
        SimpleRouter::request()->setMethod('post');

        SimpleRouter::get('/my/test/url', 'DummyController@start');

        try {
            SimpleRouter::start();
        } catch (\Exception $e) {
            $this->assertEquals(403, $e->getCode());
        }
    }

    public function testSimpleParam()
    {
        SimpleRouter::router()->reset();
        SimpleRouter::request()->setMethod('get');
        SimpleRouter::request()->setUri('/test-param1');

        SimpleRouter::get('/test-{param1}', 'DummyController@param');
        SimpleRouter::start();
    }

    public function testPathParamRegex()
    {
        SimpleRouter::router()->reset();
        SimpleRouter::request()->setMethod('get');
        SimpleRouter::request()->setUri('/test/path/123123');

        SimpleRouter::get('/test/path/{myParam}', 'DummyController@param', ['where' => ['myParam' => '([0-9]+)']]);
        SimpleRouter::start();
    }

    public function testDomainRoute()
    {
        SimpleRouter::router()->reset();
        SimpleRouter::request()->setMethod('get');
        SimpleRouter::request()->setUri('/test');
        SimpleRouter::request()->setHost('hello.world.com');

        $this->result = false;

        SimpleRouter::group(['domain' => '{subdomain}.world.com'], function () {
            SimpleRouter::get('/test', function ($subdomain = null) {
                $this->result = ($subdomain === 'hello');
            });
        });

        SimpleRouter::start();

        $this->assertTrue($this->result);

    }

}
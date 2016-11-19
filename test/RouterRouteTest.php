<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';

use Pecee\SimpleRouter\SimpleRouter as SimpleRouter;

class RouterRouteTest extends PHPUnit_Framework_TestCase
{
	protected $result = false;

	public function testNotFound()
	{
		SimpleRouter::router()->reset();
		SimpleRouter::request()->setMethod('get');
		SimpleRouter::request()->setUri('/test-param1-param2');

		SimpleRouter::group(['exceptionHandler' => 'ExceptionHandler'], function () {
			SimpleRouter::get('/non-existing-path', 'DummyController@start');
		});

		$found = false;

		try {
			SimpleRouter::start();
		} catch (\Exception $e) {
			$found = ($e instanceof \Pecee\Exception\RouterException && $e->getCode() == 404);
		}

		$this->assertTrue($found);
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

	public function testMultiParam()
	{
		SimpleRouter::router()->reset();
		SimpleRouter::request()->setMethod('get');
		SimpleRouter::request()->setUri('/test-param1-param2');

		SimpleRouter::get('/test-{param1}-{param2}', 'DummyController@param');
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
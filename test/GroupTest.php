<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';

use Pecee\SimpleRouter\SimpleRouter as SimpleRouter;

class GroupTest extends PHPUnit_Framework_TestCase
{
	protected $result;

	public function testGroupLoad()
	{
		$this->result = false;

		SimpleRouter::group(['prefix' => '/group'], function () {
			$this->result = true;
		});

		try {
			SimpleRouter::start();
		} catch (Exception $e) {
			// ignore RouteNotFound exception
		}

		$this->assertTrue($this->result);
	}

	public function testNestedGroup()
	{

		SimpleRouter::router()->reset();
		SimpleRouter::request()->setUri('/api/v1/test');
		SimpleRouter::request()->setMethod('get');

		SimpleRouter::group(['prefix' => '/api'], function () {

			SimpleRouter::group(['prefix' => '/v1'], function () {
				SimpleRouter::get('/test', 'DummyController@start');
			});

		});

		SimpleRouter::start();
	}

	public function testManyRoutes()
	{

		SimpleRouter::router()->reset();
		SimpleRouter::request()->setUri('/my/match');
		SimpleRouter::request()->setMethod('get');

		SimpleRouter::group(['prefix' => '/api'], function () {

			SimpleRouter::group(['prefix' => '/v1'], function () {
				SimpleRouter::get('/test', 'DummyController@start');
			});

		});

		SimpleRouter::get('/my/match', 'DummyController@start');

		SimpleRouter::group(['prefix' => '/service'], function () {

			SimpleRouter::group(['prefix' => '/v1'], function () {
				SimpleRouter::get('/no-match', 'DummyController@start');
			});

		});

		SimpleRouter::start();
	}

	public function testUrls()
	{

		SimpleRouter::router()->reset();
		SimpleRouter::request()->setUri('/my/fancy/url/1');
		SimpleRouter::request()->setMethod('get');

		// Test array name
		SimpleRouter::get('/my/fancy/url/1', 'DummyController@start', ['as' => 'fancy1']);

		// Test method name
		SimpleRouter::get('/my/fancy/url/2', 'DummyController@start')->setName('fancy2');

		// Test multiple names
		SimpleRouter::get('/my/fancy/url/3', 'DummyController@start', ['as' => ['fancy3', 'fancy4']]);

		SimpleRouter::start();

		$this->assertEquals('/my/fancy/url/1/', SimpleRouter::getRoute('fancy1'));
		$this->assertEquals('/my/fancy/url/2/', SimpleRouter::getRoute('fancy2'));

		$this->assertEquals('/my/fancy/url/3/', SimpleRouter::getRoute('fancy3'));
		$this->assertEquals('/my/fancy/url/3/', SimpleRouter::getRoute('fancy4'));

	}

}
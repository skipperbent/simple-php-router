<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';

use Pecee\SimpleRouter\SimpleRouter as SimpleRouter;

class RouterUrlTest extends PHPUnit_Framework_TestCase
{
	protected $result = false;

	protected function getUrl($name = null, $parameters = null, array $getParams = []) {
		return SimpleRouter::getRoute($name, $parameters, $getParams);
	}

	public function testUrls()
	{
		SimpleRouter::router()->reset();
		SimpleRouter::request()->setMethod('get');
		SimpleRouter::request()->setUri('/');

		// Match normal route on alias
		SimpleRouter::get('/', 'DummyController@silent', ['as' => 'home']);

		SimpleRouter::get('/about', 'DummyController@about');

		SimpleRouter::group(['prefix' => '/admin', 'as' => 'admin'], function() {

			// Match route with prefix on alias
			SimpleRouter::get('/{id?}', 'DummyController@start', ['as' => 'home']);

			// Match controller with prefix and alias
			SimpleRouter::controller('/users', 'DummyController', ['as' => 'users']);

			// Match controller with prefix and NO alias
			SimpleRouter::controller('/pages', 'DummyController');

		});

		SimpleRouter::group(['prefix' => 'api', 'as' => 'api'], function() {

			// Match resource controller
			SimpleRouter::resource('phones', 'DummyController');

		});

		SimpleRouter::controller('gadgets', 'DummyController', ['names' => ['getIphoneInfo' => 'iphone']]);

		// Match controller with no prefix and no alias
		SimpleRouter::controller('/cats', 'CatsController');

		// Pretend to load page
		SimpleRouter::start();

		$this->assertEquals('/gadgets/iphoneinfo/', $this->getUrl('gadgets.iphone'));

		$this->assertEquals('/api/phones/create/', $this->getUrl('api.phones.create'));

		// Should match /
		$this->assertEquals('/', $this->getUrl('home'));

		// Should match /about/
		$this->assertEquals('/about/', $this->getUrl('DummyController@about'));

		// Should match /admin/
		$this->assertEquals('/admin/', $this->getUrl('DummyController@start'));

		// Should match /admin/
		$this->assertEquals('/admin/', $this->getUrl('admin.home'));

		// Should match /admin/2/
		$this->assertEquals('/admin/2/', $this->getUrl('admin.home', ['id' => 2]));

		// Should match /admin/users/
		$this->assertEquals('/admin/users/', $this->getUrl('admin.users'));

		// Should match /admin/users/home/
		$this->assertEquals('/admin/users/home/', $this->getUrl('admin.users@home'));

		// Should match /cats/
		$this->assertEquals('/cats/', $this->getUrl('CatsController'));

		// Should match /cats/view/
		$this->assertEquals('/cats/view/', $this->getUrl('CatsController', 'view'));

		// Should match /cats/view/
		//$this->assertEquals('/cats/view/', $this->getUrl('CatsController', ['view']));

		// Should match /cats/view/666
		$this->assertEquals('/cats/view/666/', $this->getUrl('CatsController@getView', ['666']));

		// Should match /funny/man/
		$this->assertEquals('/funny/man/', $this->getUrl('/funny/man'));

		// Should match /?jackdaniels=true&cola=yeah
		$this->assertEquals('/?jackdaniels=true&cola=yeah', $this->getUrl('home', null, ['jackdaniels' => 'true', 'cola' => 'yeah']));

	}

}
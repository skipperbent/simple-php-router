<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';

use Pecee\SimpleRouter\SimpleRouter as SimpleRouter;

class RouterUrlTest extends PHPUnit_Framework_TestCase
{
	protected $result = false;

	protected function getUrl($controller = null, $parameters = null, $getParams = null) {
		return SimpleRouter::getRoute($controller, $parameters, $getParams);
	}

	public function testUrls()
	{
		SimpleRouter::router()->reset();
		SimpleRouter::request()->setMethod('get');
		SimpleRouter::request()->setUri('/');

		// Match normal route on alias
		SimpleRouter::get('/', 'DummyController@silent', ['as' => 'home']);

		SimpleRouter::group(['prefix' => '/admin'], function() {

			// Match route with prefix on alias
			SimpleRouter::get('/{id?}', 'DummyController@start', ['as' => 'admin.home']);

			// Match controller with prefix and alias
			SimpleRouter::controller('/users', 'DummyController', ['as' => 'admin.users']);

			// Match controller with prefix and NO alias
			SimpleRouter::controller('/pages', 'DummyController');

		});

		// Match controller with no prefix and no alias
		SimpleRouter::controller('/cats', 'CatsController');

		// Pretend to load page
		SimpleRouter::start();

		// Should match /
		$this->assertEquals($this->getUrl('home'), '/');

		// Should match /admin/
		$this->assertEquals($this->getUrl('DummyController@start'), '/admin/');

		// Should match /admin/
		$this->assertEquals($this->getUrl('admin.home'), '/admin/');

		// Should match /admin/2/
		$this->assertEquals($this->getUrl('admin.home', ['id' => 2]), '/admin/2/');

		// Should match /admin/users/
		$this->assertEquals($this->getUrl('admin.users'), '/admin/users/');

		// Should match /admin/users/home/
		$this->assertEquals($this->getUrl('admin.users@home'), '/admin/users/home/');

		// Should match /cats/
		$this->assertEquals($this->getUrl('CatsController'), '/cats/');

		// Should match /cats/view/
		$this->assertEquals($this->getUrl('CatsController', 'view'), '/cats/view/');

		// Should match /cats/view/
		$this->assertEquals($this->getUrl('CatsController', ['view']), '/cats/view/');

		// Should match /cats/view/666
		$this->assertEquals($this->getUrl('CatsController@view', ['666']), '/cats/view/666/');

		// Should match /funny/man/
		$this->assertEquals($this->getUrl('/funny/man'), '/funny/man/');

		// Should match /?jackdaniels=true
		$this->assertEquals($this->getUrl('home', null, ['jackdaniels' => 'true']), '/?jackdaniels=true');

	}

}
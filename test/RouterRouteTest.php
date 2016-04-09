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
        \Pecee\Http\Request::getInstance()->setMethod('get');

        $router = new \Pecee\SimpleRouter\RouterBase();

        $route = new \Pecee\SimpleRouter\RouterRoute('/my/test/url', 'DummyController@start');
        $route->setRequestMethods(array(\Pecee\SimpleRouter\RouterRoute::REQUEST_TYPE_GET));

        $router->addRoute($route);
        $router->routeRequest();
    }

    public function testPost() {
        \Pecee\Http\Request::getInstance()->setMethod('post');

        $router = new \Pecee\SimpleRouter\RouterBase();

        $route = new \Pecee\SimpleRouter\RouterRoute('/my/test/url', 'DummyController@start');

        $route->addSettings(array());
        $route->setRequestMethods(array(\Pecee\SimpleRouter\RouterRoute::REQUEST_TYPE_POST));

        $router->addRoute($route);
        $router->routeRequest();
    }

    public function testPut() {
        \Pecee\Http\Request::getInstance()->setMethod('put');

        $router = new \Pecee\SimpleRouter\RouterBase();

        $route = new \Pecee\SimpleRouter\RouterRoute('/my/test/url', 'DummyController@start');
        $route->addSettings(array());
        $route->setRequestMethods(array(\Pecee\SimpleRouter\RouterRoute::REQUEST_TYPE_PUT));

        $router->addRoute($route);
        $router->routeRequest();

    }

    public function testDelete() {
        \Pecee\Http\Request::getInstance()->setMethod('delete');

        $router = new \Pecee\SimpleRouter\RouterBase();

        $route = new \Pecee\SimpleRouter\RouterRoute('/my/test/url', 'DummyController@start');
        $route->addSettings(array());
        $route->setRequestMethods(array(\Pecee\SimpleRouter\RouterRoute::REQUEST_TYPE_DELETE));

        $router->addRoute($route);
        $router->routeRequest();

    }

    public function testMethodNotAllowed() {
        \Pecee\Http\Request::getInstance()->setMethod('post');

        \Pecee\SimpleRouter\SimpleRouter::get('/my/test/url', 'DummyController@start');

        try {
            \Pecee\SimpleRouter\SimpleRouter::start();
        } catch(\Exception $e) {
            $this->assertEquals(403, $e->getCode());
        }

    }

}
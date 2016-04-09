<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';

class MiddlewareTest extends PHPUnit_Framework_TestCase  {

    public function __construct() {
        // Initial setup
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/my/test/url';
        $_SERVER['REQUEST_METHOD'] = 'get';
    }

    public function testMiddlewareFound() {

        \Pecee\Http\Request::getInstance()->setMethod('get');

        $router = new \Pecee\SimpleRouter\RouterBase();

        $route = new \Pecee\SimpleRouter\RouterRoute('/my/test/url', 'DummyController@start');
        $route->setRequestMethods(array(\Pecee\SimpleRouter\RouterRoute::REQUEST_TYPE_GET));
        $route->addSettings(['middleware' => 'DummyMiddleware']);
        $router->addRoute($route);

        try {
            $router->routeRequest();
        }catch(Exception $e) {
            $this->assertTrue(($e instanceof MiddlewareLoadedException));
            return;
        }

        throw new Exception('Middleware not loaded');

    }



}
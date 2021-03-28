<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';
require_once 'Dummy/Managers/TestBootManager.php';
require_once 'Dummy/Managers/FindUrlBootManager.php';

class BootManagerTest extends \PHPUnit\Framework\TestCase
{

    public function testBootManagerRoutes()
    {
        $result = false;

        TestRouter::get('/', function () use (&$result) {
            $result = true;
        });
        TestRouter::get('/about', 'DummyController@method2');
        TestRouter::get('/contact', 'DummyController@method3');

        // Add boot-manager
        TestRouter::addBootManager(new TestBootManager([
            '/con'     => '/about',
            '/contact' => '/',
        ]));

        TestRouter::debug('/contact');

        $this->assertTrue($result);
    }

    public function testFindUrlFromBootManager()
    {
        TestRouter::get('/', 'DummyController@method1');
        TestRouter::get('/about', 'DummyController@method2')->name('about');
        TestRouter::get('/contact', 'DummyController@method3')->name('contact');

        $result = false;

        // Add boot-manager
        TestRouter::addBootManager(new FindUrlBootManager($result));

        TestRouter::debug('/');

        $this->assertTrue($result);
    }

}
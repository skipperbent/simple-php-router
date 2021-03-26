<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';

class RouterPartialGroupTest extends \PHPUnit\Framework\TestCase
{

    public function testParameters()
    {
        $result1 = null;
        $result2 = null;

        TestRouter::partialGroup('{param1}/{param2}', function ($param1 = null, $param2 = null) use (&$result1, &$result2) {
            $result1 = $param1;
            $result2 = $param2;

            TestRouter::get('/', 'DummyController@method1');
        });

        TestRouter::debug('/param1/param2', 'get');

        $this->assertEquals('param1', $result1);
        $this->assertEquals('param2', $result2);
    }

    /**
     * Fixed issue with partial routes not loading child groups.
     * Reported in issue: #456
     */
    public function testPartialGroupWithGroup() {

        $lang = null;

        $route1 = '/lang/da/test/';
        $route2 = '/lang/da/auth';
        $route3 = '/lang/da/auth/test';

        TestRouter::partialGroup(
            '/lang/{test}/',
            function ($lang = 'en') use($route1, $route2, $route3) {

                TestRouter::get('/test/', function () use($route1) {
                    return $route1;
                });

                TestRouter::group(['prefix' => '/auth/'], function () use($route2, $route3) {

                    TestRouter::get('/', function() use($route2) {
                        return $route2;
                    });

                    TestRouter::get('/test', function () use($route3){
                        return $route3;
                    });

                });

            }
        );

        $test1 = TestRouter::debugOutput('/lang/da/test', 'get', false);
        $test2 = TestRouter::debugOutput('/lang/da/auth', 'get', false);
        $test3 = TestRouter::debugOutput('/lang/da/auth/test', 'get', false);

        $this->assertEquals($test1, $route1);
        $this->assertEquals($test2, $route2);
        $this->assertEquals($test3, $route3);

    }

    public function testStrictNonMatch() {

        $result = false;

        TestRouter::partialGroup('/test/', function () use (&$result) {
            $result = true;

            TestRouter::get('/', 'DummyController@method1');
        }, ['strict' => true]);

        TestRouter::debug('/test/hello', 'get');

        $this->assertFalse($result);
    }

    public function testStrictMatch() {

        $result = false;

        TestRouter::router()->reset();

        TestRouter::partialGroup('/test', function () use (&$result) {
            $result = true;

            TestRouter::get('/', 'DummyController@method1');
        }, ['strict' => true]);

        TestRouter::debug('/test', 'get');

        $this->assertTrue($result);
    }

}
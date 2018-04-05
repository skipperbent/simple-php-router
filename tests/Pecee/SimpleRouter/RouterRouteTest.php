<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Exception/ExceptionHandlerException.php';

class RouterRouteTest extends \PHPUnit\Framework\TestCase
{
    public function testMultiParam()
    {
        $result = false;
        TestRouter::get('/test-{param1}-{param2}', function ($param1, $param2) use(&$result) {

            if ($param1 === 'param1' && $param2 === 'param2') {
                $result = true;
            }

        });

        TestRouter::debug('/test-param1-param2', 'get');

        $this->assertTrue($result);

    }

    public function testNotFound()
    {
        $this->expectException('\Pecee\SimpleRouter\Exceptions\NotFoundHttpException');
        TestRouter::get('/non-existing-path', 'DummyController@method1');
        TestRouter::debug('/test-param1-param2', 'post');
    }

    public function testGet()
    {
        TestRouter::get('/my/test/url', 'DummyController@method1');
        TestRouter::debug('/my/test/url', 'get');

        $this->assertTrue(true);
    }

    public function testPost()
    {
        TestRouter::post('/my/test/url', 'DummyController@method1');
        TestRouter::debug('/my/test/url', 'post');

        $this->assertTrue(true);
    }

    public function testPut()
    {
        TestRouter::put('/my/test/url', 'DummyController@method1');
        TestRouter::debug('/my/test/url', 'put');

        $this->assertTrue(true);
    }

    public function testDelete()
    {
        TestRouter::delete('/my/test/url', 'DummyController@method1');
        TestRouter::debug('/my/test/url', 'delete');

        $this->assertTrue(true);
    }

    public function testMethodNotAllowed()
    {
        TestRouter::get('/my/test/url', 'DummyController@method1');

        try {
            TestRouter::debug('/my/test/url', 'post');
        } catch (\Exception $e) {
            $this->assertEquals(403, $e->getCode());
        }
    }

    public function testSimpleParam()
    {
        TestRouter::get('/test-{param1}', 'DummyController@param');
        $response = TestRouter::debugOutput('/test-param1', 'get');

        $this->assertEquals('param1', $response);
    }

    public function testPathParamRegex()
    {
        TestRouter::get('/{lang}/productscategories/{name}', 'DummyController@param', ['where' => ['lang' => '[a-z]+', 'name' => '[A-Za-z0-9\-]+']]);
        $response = TestRouter::debugOutput('/it/productscategories/system', 'get');

        $this->assertEquals('it, system', $response);
    }

    public function testDomainAllowedRoute()
    {
        $result = false;
        TestRouter::request()->setHost('hello.world.com');

        TestRouter::group(['domain' => '{subdomain}.world.com'], function () use(&$result) {
            TestRouter::get('/test', function ($subdomain = null) use(&$result) {
                $result = ($subdomain === 'hello');
            });
        });


        TestRouter::debug('/test', 'get');

        $this->assertTrue($result);

    }

    public function testDomainNotAllowedRoute()
    {
        TestRouter::request()->setHost('other.world.com');

        $result = false;

        TestRouter::group(['domain' => '{subdomain}.world.com'], function () use(&$result) {
            TestRouter::get('/test', function ($subdomain = null) use(&$result) {
                $result = ($subdomain === 'hello');
            });
        });

        TestRouter::debug('/test', 'get');

        $this->assertFalse($result);

    }

    public function testRegEx()
    {
        TestRouter::get('/my/{path}', 'DummyController@method1')->where(['path' => '[a-zA-Z\-]+']);
        TestRouter::debug('/my/custom-path', 'get');

        $this->assertTrue(true);
    }

    public function testParameterDefaultValue() {

        $defaultVariable = null;

        TestRouter::get('/my/{path?}', function($path = 'working') use(&$defaultVariable) {
            $defaultVariable = $path;
        });

        TestRouter::debug('/my/');

        $this->assertEquals('working', $defaultVariable);

    }

    public function testDefaultParameterRegex()
    {
        TestRouter::get('/my/{path}', 'DummyController@param', ['defaultParameterRegex' => '[\w\-]+']);
        $output = TestRouter::debugOutput('/my/custom-regex', 'get');

        $this->assertEquals('custom-regex', $output);
    }

    public function testDefaultParameterRegexGroup()
    {
        TestRouter::group(['defaultParameterRegex' => '[\w\-]+'], function() {
            TestRouter::get('/my/{path}', 'DummyController@param');
        });

        $output = TestRouter::debugOutput('/my/custom-regex', 'get');

        $this->assertEquals('custom-regex', $output);
    }

}
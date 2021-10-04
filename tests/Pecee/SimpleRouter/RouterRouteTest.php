<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/NSController.php';
require_once 'Dummy/Exception/ExceptionHandlerException.php';

class RouterRouteTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Issue #421: Incorrectly optional character in route
     *
     * @throws Exception
     */
    public function testOptionalCharacterRoute()
    {
        $result = false;
        TestRouter::get('/api/v1/users/{userid}/projects/{id}/pages/{pageid?}', function () use (&$result) {
            $result = true;
        });

        TestRouter::debug('/api/v1/users/1/projects/8399421535/pages/43/', 'get');

        $this->assertTrue($result);
    }

    public function testMultiParam()
    {
        $result = false;
        TestRouter::get('/test-{param1}-{param2}', function ($param1, $param2) use (&$result) {

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
        TestRouter::get('/{lang}/productscategories/{name}', 'DummyController@param', ['where' => ['lang' => '[a-z]+', 'name' => '[A-Za-z0-9-]+']]);
        $response = TestRouter::debugOutput('/it/productscategories/system', 'get');

        $this->assertEquals('it, system', $response);
    }

    public function testFixedDomain()
    {
        $result = false;
        TestRouter::request()->setHost('admin.world.com');

        TestRouter::group(['domain' => 'admin.world.com'], function () use (&$result) {
            TestRouter::get('/test', function ($subdomain = null) use (&$result) {
                $result = true;
            });
        });

        TestRouter::debug('/test', 'get');

        $this->assertTrue($result);
    }

    public function testFixedNotAllowedDomain()
    {
        $result = false;
        TestRouter::request()->setHost('other.world.com');

        TestRouter::group(['domain' => 'admin.world.com'], function () use (&$result) {
            TestRouter::get('/', function ($subdomain = null) use (&$result) {
                $result = true;
            });
        });

        try {
            TestRouter::debug('/', 'get');
        } catch(\Exception $e) {

        }

        $this->assertFalse($result);
    }

    public function testDomainAllowedRoute()
    {
        $result = false;
        TestRouter::request()->setHost('hello.world.com');

        TestRouter::group(['domain' => '{subdomain}.world.com'], function () use (&$result) {
            TestRouter::get('/test', function ($subdomain = null) use (&$result) {
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

        TestRouter::group(['domain' => '{subdomain}.world.com'], function () use (&$result) {
            TestRouter::get('/test', function ($subdomain = null) use (&$result) {
                $result = ($subdomain === 'hello');
            });
        });

        TestRouter::debug('/test', 'get');

        $this->assertFalse($result);

    }    
    
    public function testFixedSubdomainDynamicDomain()
    {
        TestRouter::request()->setHost('other.world.com');

        $result = false;

        TestRouter::group(['domain' => 'other.{domain}'], function () use (&$result) {
            TestRouter::get('/test', function ($domain = null) use (&$result) {

                $result = true;
            });
        });

        TestRouter::debug('/test', 'get');

        $this->assertTrue($result);

    }

    public function testFixedSubdomainDynamicDomainParameter()
    {
        TestRouter::request()->setHost('other.world.com');

        $result = false;

        TestRouter::group(['domain' => 'other.{domain}'], function () use (&$result) {
            TestRouter::get('/test', 'DummyController@param');
            TestRouter::get('/test/{key}', 'DummyController@param');
        });

        $response = TestRouter::debugOutputNoReset('/test', 'get');

        $this->assertEquals('world.com', $response);

        $response = TestRouter::debugOutput('/test/unittest', 'get');

        $this->assertEquals('unittest, world.com', $response);

    }

    public function testWrongFixedSubdomainDynamicDomain()
    {
        TestRouter::request()->setHost('wrong.world.com');

        $result = false;

        TestRouter::group(['domain' => 'other.{domain}'], function () use (&$result) {
            TestRouter::get('/test', function ($domain = null) use (&$result) {

                $result = true;
            });
        });

        try {
            TestRouter::debug('/test', 'get');
        } catch(\Exception $e) {

        }


        $this->assertFalse($result);

    }

    public function testRegEx()
    {
        TestRouter::get('/my/{path}', 'DummyController@method1')->where(['path' => '[a-zA-Z-]+']);
        TestRouter::debug('/my/custom-path', 'get');

        $this->assertTrue(true);
    }

    public function testParametersWithDashes()
    {

        $defaultVariable = null;

        TestRouter::get('/my/{path}', function ($path = 'working') use (&$defaultVariable) {
            $defaultVariable = $path;
        });

        TestRouter::debug('/my/hello-motto-man');

        $this->assertEquals('hello-motto-man', $defaultVariable);

    }

    public function testParameterDefaultValue()
    {

        $defaultVariable = null;

        TestRouter::get('/my/{path?}', function ($path = 'working') use (&$defaultVariable) {
            $defaultVariable = $path;
        });

        TestRouter::debug('/my/');

        $this->assertEquals('working', $defaultVariable);

    }

    public function testDefaultParameterRegex()
    {
        TestRouter::get('/my/{path}', 'DummyController@param', ['defaultParameterRegex' => '[\w-]+']);
        $output = TestRouter::debugOutput('/my/custom-regex', 'get');

        $this->assertEquals('custom-regex', $output);
    }

    public function testDefaultParameterRegexGroup()
    {
        TestRouter::group(['defaultParameterRegex' => '[\w-]+'], function () {
            TestRouter::get('/my/{path}', 'DummyController@param');
        });

        $output = TestRouter::debugOutput('/my/custom-regex', 'get');

        $this->assertEquals('custom-regex', $output);
    }

    public function testClassHint()
    {
        TestRouter::get('/my/test/url', ['DummyController', 'method1']);
        TestRouter::all('/my/test/url', ['DummyController', 'method1']);
        TestRouter::match(['put', 'get', 'post'], '/my/test/url', ['DummyController', 'method1']);

        TestRouter::debug('/my/test/url', 'get');

        $this->assertTrue(true);
    }

    public function testDefaultNameSpaceOverload()
    {
        TestRouter::setDefaultNamespace('DefaultNamespace\\Controllers');
        TestRouter::get('/test', [\MyNamespace\NSController::class, 'method']);

        $result = TestRouter::debugOutput('/test');

        $this->assertTrue( (bool)$result);
    }

    public function testSameRoutes()
    {
        TestRouter::get('/recipe', 'DummyController@method1')->name('add');
        TestRouter::post('/recipe', 'DummyController@method2')->name('edit');

        TestRouter::debugNoReset('/recipe', 'post');
        TestRouter::debug('/recipe', 'get');

        $this->assertTrue(true);
    }

}
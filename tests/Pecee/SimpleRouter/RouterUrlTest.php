<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';

class RouterUrlTest extends \PHPUnit\Framework\TestCase
{

    public function testIssue253()
    {
        TestRouter::get('/', 'DummyController@method1');
        TestRouter::get('/page/{id?}', 'DummyController@method1');
        TestRouter::get('/test-output', function () {
            return 'return value';
        });

        TestRouter::debugNoReset('/page/22', 'get');
        $this->assertEquals('/page/{id?}/', TestRouter::router()->getRequest()->getLoadedRoute()->getUrl());

        TestRouter::debugNoReset('/', 'get');
        $this->assertEquals('/', TestRouter::router()->getRequest()->getLoadedRoute()->getUrl());

        $output = TestRouter::debugOutput('/test-output', 'get');
        $this->assertEquals('return value', $output);

        TestRouter::router()->reset();
    }

    public function testLastParameterSlash()
    {
        TestRouter::get('/test/{param}', function ($param) {
            return $param;
        })->setSettings(['includeSlash' => true]);

        // Test with ending /
        $output = TestRouter::debugOutputNoReset('/test/param/');
        $this->assertEquals($output, 'param/');

        // Test without ending /
        $output = TestRouter::debugOutputNoReset('/test/param');
        $this->assertEquals($output, 'param');

        TestRouter::router()->reset();
    }

    public function testUnicodeCharacters()
    {
        // Test spanish characters
        TestRouter::get('/cursos/listado/{listado?}/{category?}', 'DummyController@method1', ['defaultParameterRegex' => '[\w\p{L}\s\-]+']);
        TestRouter::get('/test/{param}', 'DummyController@method1', ['defaultParameterRegex' => '[\w\p{L}\s\-\í]+']);
        TestRouter::debugNoReset('/cursos/listado/especialidad/cirugía local', 'get');

        $this->assertEquals('/cursos/listado/{listado?}/{category?}/', TestRouter::router()->getRequest()->getLoadedRoute()->getUrl());

        TestRouter::debugNoReset('/test/Dermatología');
        $parameters = TestRouter::request()->getLoadedRoute()->getParameters();

        $this->assertEquals('Dermatología', $parameters['param']);

        // Test danish characters
        TestRouter::get('/kategori/økse', 'DummyController@method1', ['defaultParameterRegex' => '[\w\ø]+']);
        TestRouter::debugNoReset('/kategori/økse', 'get');

        $this->assertEquals('/kategori/økse/', TestRouter::router()->getRequest()->getLoadedRoute()->getUrl());

        TestRouter::router()->reset();
    }

    public function testOptionalParameters()
    {
        TestRouter::get('/aviso/legal', 'DummyController@method1');
        TestRouter::get('/aviso/{aviso}', 'DummyController@method1');
        TestRouter::get('/pagina/{pagina}', 'DummyController@method1');
        TestRouter::get('/{pagina?}', 'DummyController@method1');

        TestRouter::debugNoReset('/aviso/optional', 'get');
        $this->assertEquals('/aviso/{aviso}/', TestRouter::router()->getRequest()->getLoadedRoute()->getUrl());

        TestRouter::debugNoReset('/pagina/optional', 'get');
        $this->assertEquals('/pagina/{pagina}/', TestRouter::router()->getRequest()->getLoadedRoute()->getUrl());

        TestRouter::debugNoReset('/optional', 'get');
        $this->assertEquals('/{pagina?}/', TestRouter::router()->getRequest()->getLoadedRoute()->getUrl());

        TestRouter::debugNoReset('/avisolegal', 'get');
        $this->assertNotEquals('/aviso/{aviso}/', TestRouter::router()->getRequest()->getLoadedRoute()->getUrl());

        TestRouter::debugNoReset('/avisolegal', 'get');
        $this->assertEquals('/{pagina?}/', TestRouter::router()->getRequest()->getLoadedRoute()->getUrl());

        TestRouter::router()->reset();
    }

    public function testSimilarUrls()
    {
        TestRouter::reset();
        // Match normal route on alias
        TestRouter::get('/url11', 'DummyController@method1');
        TestRouter::get('/url22', 'DummyController@method2');
        TestRouter::get('/url33', 'DummyController@method2')->name('match');


        TestRouter::debugNoReset('/url33', 'get');

        $this->assertEquals(TestRouter::getUrl('match'), TestRouter::getUrl());

        TestRouter::router()->reset();
    }

    public function testUrls()
    {
        // Match normal route on alias
        TestRouter::get('/', 'DummyController@method1', ['as' => 'home']);

        TestRouter::get('/about', 'DummyController@about');

        TestRouter::group(['prefix' => '/admin', 'as' => 'admin'], function () {

            // Match route with prefix on alias
            TestRouter::get('/{id?}', 'DummyController@method2', ['as' => 'home']);

            // Match controller with prefix and alias
            TestRouter::controller('/users', 'DummyController', ['as' => 'users']);

            // Match controller with prefix and NO alias
            TestRouter::controller('/pages', 'DummyController');

        });

        TestRouter::group(['prefix' => 'api', 'as' => 'api'], function () {

            // Match resource controller
            TestRouter::resource('phones', 'DummyController');

        });

        TestRouter::controller('gadgets', 'DummyController', ['names' => ['getIphoneInfo' => 'iphone']]);

        // Match controller with no prefix and no alias
        TestRouter::controller('/cats', 'CatsController');

        // Pretend to load page
        TestRouter::debugNoReset('/', 'get');

        $this->assertEquals('/gadgets/iphoneinfo/', TestRouter::getUrl('gadgets.iphone'));

        $this->assertEquals('/api/phones/create/', TestRouter::getUrl('api.phones.create'));

        // Should match /
        $this->assertEquals('/', TestRouter::getUrl('home'));

        // Should match /about/
        $this->assertEquals('/about/', TestRouter::getUrl('DummyController@about'));

        // Should match /admin/
        $this->assertEquals('/admin/', TestRouter::getUrl('DummyController@method2'));

        // Should match /admin/
        $this->assertEquals('/admin/', TestRouter::getUrl('admin.home'));

        // Should match /admin/2/
        $this->assertEquals('/admin/2/', TestRouter::getUrl('admin.home', ['id' => 2]));

        // Should match /admin/users/
        $this->assertEquals('/admin/users/', TestRouter::getUrl('admin.users'));

        // Should match /admin/users/home/
        $this->assertEquals('/admin/users/home/', TestRouter::getUrl('admin.users@home'));

        // Should match /cats/
        $this->assertEquals('/cats/', TestRouter::getUrl('CatsController'));

        // Should match /cats/view/
        $this->assertEquals('/cats/view/', TestRouter::getUrl('CatsController', 'view'));

        // Should match /cats/view/
        //$this->assertEquals('/cats/view/', TestRouter::getUrl('CatsController', ['view']));

        // Should match /cats/view/666
        $this->assertEquals('/cats/view/666/', TestRouter::getUrl('CatsController@getView', ['666']));

        // Should match /funny/man/
        $this->assertEquals('/funny/man/', TestRouter::getUrl('/funny/man'));

        // Should match /?jackdaniels=true&cola=yeah
        $this->assertEquals('/?jackdaniels=true&cola=yeah', TestRouter::getUrl('home', null, ['jackdaniels' => 'true', 'cola' => 'yeah']));

        TestRouter::reset();

    }

    public function testCustomRegex()
    {
        TestRouter::request()->setHost('google.com');

        TestRouter::get('/admin/', function () {
            return 'match';
        })->setMatch('/^\/admin\/?(.*)/i');

        $output = TestRouter::debugOutput('/admin/asd/bec/123', 'get');
        $this->assertEquals('match', $output);

        TestRouter::router()->reset();
    }

    public function testCustomRegexWithParameter()
    {
        TestRouter::request()->setHost('google.com');

        $results = '';

        TestRouter::get('/tester/{param}', function ($param = null) use ($results) {
            return $results = $param;
        })->setMatch('/(.*)/i');

        $output = TestRouter::debugOutput('/tester/abepik/ko');
        $this->assertEquals('/tester/abepik/ko/', $output);
    }

    public function testRenderMultipleRoutesDisabled()
    {
        TestRouter::router()->setRenderMultipleRoutes(false);

        $result = false;

        TestRouter::get('/', function () use (&$result) {
            $result = true;
        });

        TestRouter::get('/', function () use (&$result) {
            $result = false;
        });

        TestRouter::debug('/');

        $this->assertTrue($result);
    }

    public function testRenderMultipleRoutesEnabled()
    {
        TestRouter::router()->setRenderMultipleRoutes(true);

        $result = [];

        TestRouter::get('/', function () use (&$result) {
            $result[] = 'route1';
        });

        TestRouter::get('/', function () use (&$result) {
            $result[] = 'route2';
        });

        TestRouter::debug('/');

        $this->assertCount(2, $result);
    }

    public function testDefaultNamespace()
    {
        TestRouter::setDefaultNamespace('\\Default\\Namespace');

        TestRouter::get('/', 'DummyController@method1', ['as' => 'home']);

        TestRouter::group([
            'namespace' => 'Appended\Namespace',
            'prefix' => '/horses',
        ], function () {

            TestRouter::get('/', 'DummyController@method1');

            TestRouter::group([
                'namespace' => '\\New\\Namespace',
                'prefix' => '/race',
            ], function () {

                TestRouter::get('/', 'DummyController@method1');

            });
        });

        // Test appended namespace

        $class = null;

        try {
            TestRouter::debugNoReset('/horses/');
        } catch (\Pecee\SimpleRouter\Exceptions\ClassNotFoundHttpException $e) {
            $class = $e->getClass();
        }

        $this->assertEquals('\\Default\\Namespace\\Appended\Namespace\\DummyController', $class);

        // Test overwritten namespace

        $class = null;

        try {
            TestRouter::debugNoReset('/horses/race');
        } catch (\Pecee\SimpleRouter\Exceptions\ClassNotFoundHttpException $e) {
            $class = $e->getClass();
        }

        $this->assertEquals('\\New\\Namespace\\DummyController', $class);

        TestRouter::router()->reset();
    }

    public function testGroupPrefix()
    {

        $result = false;

        TestRouter::group(['prefix' => '/lang/{lang}'], function () use (&$result) {

            TestRouter::get('/test', function () use (&$result) {
                $result = true;
            });
        });

        TestRouter::debug('/lang/da/test');

        $this->assertTrue($result);

        // Test group prefix sub-route

        $result = null;
        $expectedResult = 28;

        TestRouter::group(['prefix' => '/lang/{lang}'], function () use (&$result) {

            TestRouter::get('/horse/{horseType}', function ($horseType) use (&$result) {
                $result = false;
            });

            TestRouter::get('/user/{userId}', function ($userId) use (&$result) {
                $result = $userId;
            });
        });

        TestRouter::debug("/lang/da/user/$expectedResult");

        $this->assertEquals($expectedResult, $result);

    }

    public function testPassParameter()
    {

        $result = false;
        $expectedLanguage = 'da';

        TestRouter::group(['prefix' => '/lang/{lang}'], function ($language) use (&$result) {

            TestRouter::get('/test', function ($language) use (&$result) {
                $result = $language;
            });

        });

        TestRouter::debug("/lang/$expectedLanguage/test");

        $this->assertEquals($expectedLanguage, $result);

    }

    public function testPassParameterDeep()
    {

        $result = false;
        $expectedLanguage = 'da';

        TestRouter::group(['prefix' => '/lang/{lang}'], function ($language) use (&$result) {

            TestRouter::group(['prefix' => '/admin'], function ($language) use (&$result) {
                TestRouter::get('/test', function ($language) use (&$result) {
                    $result = $language;
                });
            });

        });

        TestRouter::debug("/lang/$expectedLanguage/admin/test");

        $this->assertEquals($expectedLanguage, $result);

    }

}
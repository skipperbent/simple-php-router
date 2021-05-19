<?php

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Exception/ExceptionHandlerException.php';

class RouterCallbackExceptionHandlerTest extends \PHPUnit\Framework\TestCase
{

    public function testCallbackExceptionHandler()
    {
        $this->expectException(ExceptionHandlerException::class);

        // Match normal route on alias
        TestRouter::get('/my-new-url', 'DummyController@method2');
        TestRouter::get('/my-url', 'DummyController@method1');

        TestRouter::error(function (\Pecee\Http\Request $request, \Exception $exception) {
            throw new ExceptionHandlerException();
        });

        TestRouter::debug('/404-url');
    }

    public function testExceptionHandlerCallback() {

        TestRouter::group(['prefix' => null], function() {
            TestRouter::get('/', function() {
                return 'Hello world';
            });

            TestRouter::get('/not-found', 'DummyController@method1');
            TestRouter::error(function(\Pecee\Http\Request $request, \Exception $exception) {

                if($exception instanceof \Pecee\SimpleRouter\Exceptions\NotFoundHttpException && $exception->getCode() === 404) {
                    return $request->setRewriteCallback(static function() {
                        return 'success';
                    });
                }
            });
        });

        $result = TestRouter::debugOutput('/thisdoes-not/existssss', 'get');
        $this->assertEquals('success', $result);
    }

}
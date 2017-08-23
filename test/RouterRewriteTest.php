<?php
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Exceptions/ResponseException.php';
require_once 'Dummy/Handler/ExceptionHandlerFirst.php';
require_once 'Dummy/Handler/ExceptionHandlerSecond.php';
require_once 'Dummy/Handler/ExceptionHandlerThird.php';
require_once 'Helpers/TestRouter.php';

class RouteRewriteTest extends PHPUnit_Framework_TestCase
{

    /**
     * Redirects to another route through 3 exception handlers.
     *
     * You will see "ExceptionHandler 1 loaded" 2 times. This happen because
     * the exceptionhandler is asking the router to reload.
     *
     * That means that the exceptionhandler is loaded again, but this time
     * the router ignores the same rewrite-route to avoid loop - loads
     * the second which have same behavior and is also ignored before
     * throwing the final Exception in ExceptionHandler 3.
     *
     * So this tests:
     * 1. If ExceptionHandlers loads
     * 2. If ExceptionHandlers load in the correct order
     * 3. If ExceptionHandlers can rewrite the page on error
     * 4. If the router can avoid redirect-loop due to developer has started loop.
     * 5. And finally if we reaches the last exception-handler and that the correct
     *    exception-type is being thrown.
     */
    public function testExceptionHandlerRewrite()
    {
        global $stack;
        $stack = [];

        TestRouter::group(['exceptionHandler' => [ExceptionHandlerFirst::class, ExceptionHandlerSecond::class]], function () {

            TestRouter::group(['exceptionHandler' => ExceptionHandlerThird::class], function () {

                TestRouter::get('/my-path', 'DummyController@method1');

            });
        });

        try {
            TestRouter::debug('/my-non-existing-path', 'get');
        } catch (\ResponseException $e) {

        }

        $expectedStack = [
            ExceptionHandlerFirst::class,
            ExceptionHandlerSecond::class,
            ExceptionHandlerThird::class,
        ];

        $this->assertEquals($expectedStack, $stack);

    }

    public function testRewriteExceptionMessage()
    {
        $this->setExpectedException(\Pecee\SimpleRouter\Exceptions\NotFoundHttpException::class);

        TestRouter::error(function (\Pecee\Http\Request $request, \Exception $error) {

            if (strtolower($request->getUri()->getPath()) == '/my/test') {
                $request->setRewriteUrl('/another-non-existing');

                return $request;
            }

        });

        TestRouter::debug('/my/test', 'get');
    }

}
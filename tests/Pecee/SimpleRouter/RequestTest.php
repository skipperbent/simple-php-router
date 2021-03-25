<?php

use Pecee\Http\Input\InputFile;

require_once 'Dummy/DummyMiddleware.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';

class RequestTest extends \PHPUnit\Framework\TestCase
{

    public function testContentTypeParse()
    {
        global $_SERVER;

        $contentType = 'application/x-www-form-urlencoded';
        $_SERVER['content_type'] = $contentType;

        $router = TestRouter::router();
        $router->reset();

        $request = $router->getRequest();

        $this->assertEquals($contentType, $request->getContentType());

        // Test special content-types
        $router->reset();

        $_SERVER['content_type'] = 'application/x-www-form-urlencoded; charset=UTF-8';

        $this->assertEquals($contentType, $request->getContentType());

        $router->reset();
    }

    // TODO: implement more test-cases

}
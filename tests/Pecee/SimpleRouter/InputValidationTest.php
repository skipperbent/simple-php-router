<?php

require_once 'Dummy/DummyInputValidator.php';
require_once 'Dummy/DummyController.php';
require_once 'Dummy/Handler/ExceptionHandler.php';

class InputValidationTest extends \PHPUnit\Framework\TestCase
{
    public function testInputValidator()
    {
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann'
        ];
        TestRouter::router()->reset();

        TestRouter::get('/my/test/url', 'DummyController@method3')->addInputValidator('DummyInputValidator');

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testInputValidatorFailed()
    {

        $this->expectException(\Pecee\Http\Input\Exceptions\InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method1')->addInputValidator('DummyInputValidator');

        TestRouter::debug('/my/test/url', 'get');
    }

}
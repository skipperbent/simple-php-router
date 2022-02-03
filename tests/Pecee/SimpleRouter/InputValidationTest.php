<?php

use Pecee\Http\Input\InputValidator;
use Pecee\Http\Input\InputValidatorItem;
use Pecee\Http\Request;

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

        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs(
                InputValidator::make()
                    ->add(
                        InputValidatorItem::make('fullname')
                            ->isString()
                    )
            );

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testInputValidatorFailed()
    {
        global $_GET;

        $_GET = [];
        TestRouter::router()->reset();

        $this->expectException(\Pecee\Http\Input\Exceptions\InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method1')
            ->validateInputs(
                InputValidator::make()
                    ->add(
                        InputValidatorItem::make('fullname')
                            ->isString()
                    )
            );

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testInputValidator2()
    {
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann'
        ];
        TestRouter::router()->reset();

        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'fullname' => 'string|max:50'
            ]);

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testInputValidatorFailed2()
    {
        global $_GET;

        $_GET = [];
        TestRouter::router()->reset();

        $this->expectException(\Pecee\Http\Input\Exceptions\InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method1')
            ->validateInputs([
                'fullname' => 'string|max:50'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testInputValidatorFailed3()
    {
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann'
        ];
        TestRouter::router()->reset();

        $this->expectException(\Pecee\Http\Input\Exceptions\InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method1')
            ->validateInputs([
                'fullname' => 'string|max:5'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

}
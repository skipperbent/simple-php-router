<?php

use Pecee\Http\Input\Exceptions\InputValidationException;
use Pecee\Http\Input\InputValidator;
use Pecee\Http\Input\InputValidatorItem;

require_once 'Dummy/InputValidatorRules/ValidatorRuleCustom.php';
require_once 'Dummy/DummyController.php';

class InputValidationTest extends \PHPUnit\Framework\TestCase
{
    public function testInputValidator()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann'
        ];

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
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [];

        $this->expectException(InputValidationException::class);
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
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann'
        ];

        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'fullname' => 'string|max:50'
            ]);

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testInputValidatorFailed2()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [];

        $this->expectException(InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method1')
            ->validateInputs([
                'fullname' => 'string|max:50'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testInputValidatorFailed3()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann'
        ];

        $this->expectException(InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method1')
            ->validateInputs([
                'fullname' => 'string|max:5'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testCustomInputValidatorRule()
    {
        InputValidator::setCustomValidatorRuleNamespace('Dummy\InputValidatorRules');

        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'customParam' => 'customValue'
        ];

        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'customParam' => 'custom'
            ]);

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testCustomInputValidatorRuleFailed()
    {
        InputValidator::setCustomValidatorRuleNamespace('Dummy\InputValidatorRules');

        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'customParam' => 'notCustomValue'
        ];

        $this->expectException(InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'customParam' => 'custom'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

}
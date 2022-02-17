<?php

use Pecee\Http\Input\Exceptions\InputValidationException;
use Pecee\Http\Input\InputValidator;
use Pecee\Http\Input\InputValidatorItem;
use Pecee\Http\Input\ValidatorRules\ValidatorRuleCustom;

require_once 'Dummy/InputValidatorRules/ValidatorRuleCustomTest.php';
require_once 'Dummy/DummyController.php';

class InputValidationTest extends \PHPUnit\Framework\TestCase
{
    public function testInputValidator()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'false',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22',
        ];

        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs(
                InputValidator::make()
                    ->add(
                        InputValidatorItem::make('fullname')->string()->max(14)->startsWith('Max')->endsWith('mann')
                    )
                    ->add(
                        InputValidatorItem::make('isAdmin')->boolean()
                    )
                    ->add(
                        InputValidatorItem::make('email')->email()
                    )
                    ->add(
                        InputValidatorItem::make('ip')->ip()
                    )
                    ->add(
                        InputValidatorItem::make('nullable')->nullable()
                    )
            );

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testInputValidatorFailed()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'not true',
            'email' => 'user#provider.com',
            'ip' => '192.168.1s05.22',
        ];

        $this->expectException(InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method1')
            ->validateInputs(
                InputValidator::make()
                    ->add(
                        InputValidatorItem::make('fullname')->string()->max(10)->startsWith('Maxx')->endsWith('x')
                    )
                    ->add(
                        InputValidatorItem::make('isAdmin')->boolean()
                    )
                    ->add(
                        InputValidatorItem::make('email')->email()
                    )
                    ->add(
                        InputValidatorItem::make('ip')->ip()
                    )
                    ->add(
                        InputValidatorItem::make('nullable')->required()
                    )
            );

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testInputValidator2()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'false',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22',
        ];

        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'fullname' => 'string|max:14|starts_with:Max|ends_with:mann',
                'isAdmin' => 'boolean',
                'email' => 'email',
                'ip' => 'ip',
                'nullable' => 'nullable'
            ]);

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testInputValidatorFailed2()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'not true',
            'email' => 'user#provider.com',
            'ip' => '192.168.1s05.22',
        ];

        $this->expectException(InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method1')
            ->validateInputs([
                'fullname' => 'string|max:10|starts_with:Maxx|ends_with:x',
                'isAdmin' => 'boolean',
                'email' => 'email',
                'ip' => 'ip',
                'nullable' => 'required'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testInputValidatorFailed3()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'not true',
            'email' => 'user#provider.com',
            'ip' => '192.168.1s05.22',
        ];

        $this->expectException(InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method1')
            ->validateInputs([
                'fullname' => 'string|max:10|starts_with:Maxx|ends_with:x',
                'isAdmin' => 'boolean',
                'email' => 'email',
                'ip' => 'ip',
                'nullable' => 'required'
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
                'customParam' => 'customTest'
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

    public function testCustomInputValidatorRule2()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'customParam' => 'customValue'
        ];

        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'customParam' => 'Dummy\InputValidatorRules\ValidatorRuleCustom'
            ]);

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testCustomInputValidatorRuleFailed2()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'customParam' => 'notCustomValue'
        ];

        $this->expectException(InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'customParam' => 'Dummy\InputValidatorRules\ValidatorRuleCustomTest'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testCustomInputValidatorRule3()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'customParam' => 'notCustomValue'
        ];

        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'customParam' => [
                    ValidatorRuleCustom::make(function(\Pecee\Http\Input\IInputItem $item){
                        return $item->getValue() == 'notCustomValue';
                    })
                ]
            ]);

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testCustomInputValidatorRuleFailed3()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'customParam' => 'notCustomValue'
        ];

        $this->expectException(InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'customParam' => [
                    ValidatorRuleCustom::make(function(\Pecee\Http\Input\IInputItem $item){
                        return $item->getValue() !== 'notCustomValue';
                    })
                ]
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testCustomInputValidatorRuleRequireFailed()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'emailAddress' => 1
        ];

        $this->expectException(InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'emailAddress' => 'email'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

}
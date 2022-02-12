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
                        InputValidatorItem::make('isAdmin')->required()->boolean()
                    )
                    ->add(
                        InputValidatorItem::make('email')->required()->email()
                    )
                    ->add(
                        InputValidatorItem::make('ip')->required()->ip()
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
                        InputValidatorItem::make('isAdmin')->required()->boolean()
                    )
                    ->add(
                        InputValidatorItem::make('email')->required()->email()
                    )
                    ->add(
                        InputValidatorItem::make('ip')->required()->ip()
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
                'isAdmin' => 'required|boolean',
                'email' => 'required|email',
                'ip' => 'required|ip',
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
                'isAdmin' => 'required|boolean',
                'email' => 'required|email',
                'ip' => 'reuquired|ip',
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
                'isAdmin' => 'required|boolean',
                'email' => 'required|email',
                'ip' => 'reuquired|ip',
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
                'customParam' => 'Dummy\InputValidatorRules\ValidatorRuleCustom'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

}
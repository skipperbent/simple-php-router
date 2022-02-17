<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleBoolean extends InputValidatorRule
{

    protected $tag = 'boolean';

    public function validate(IInputItem $inputItem): bool
    {
        $accepted = [true, false, 'true', 'false', 1, 0, '1', '0'];
        return in_array($inputItem->getValue(), $accepted, true);
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not of type boolean';
    }

}
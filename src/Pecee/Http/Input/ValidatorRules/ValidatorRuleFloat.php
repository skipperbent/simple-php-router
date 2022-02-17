<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleFloat extends InputValidatorRule
{

    protected $tag = 'float';
    protected $requires = array('required');

    public function validate(IInputItem $inputItem): bool
    {
        return is_float($inputItem->getValue()) || is_int($inputItem->getValue()) || is_numeric($inputItem->getValue());
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not of type float';
    }

}
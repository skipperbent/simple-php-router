<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleString extends InputValidatorRule
{

    protected $tag = 'string';
    protected $requires = array('required');

    public function validate(IInputItem $inputItem): bool
    {
        return is_string($inputItem->getValue());
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not of type string';
    }

}
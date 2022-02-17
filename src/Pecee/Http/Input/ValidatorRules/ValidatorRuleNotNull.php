<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleNotNull extends InputValidatorRule
{

    protected $tag = 'not_null';
    //Redundant, because default is null if not exists
    //protected $requires = array('required');

    public function validate(IInputItem $inputItem): bool
    {
        return $inputItem->getValue() !== null;
    }

    public function getErrorMessage(): string
    {
        return '';
    }

}
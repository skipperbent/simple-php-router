<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleInteger extends InputValidatorRule
{

    protected $tag = 'integer';
    protected $requires = array('required');

    public function validate(IInputItem $inputItem): bool
    {
        return is_int($inputItem->getValue()) || (is_numeric($inputItem->getValue()) && strpos($inputItem->getValue(), '.') === false);
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not of type integer';
    }

}
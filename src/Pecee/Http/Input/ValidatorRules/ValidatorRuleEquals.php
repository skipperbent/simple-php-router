<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleEquals extends InputValidatorRule
{

    protected $tag = 'equals';
    protected $requires = array('required');

    public function validate(IInputItem $inputItem): bool
    {
        if(sizeof($this->getAttributes()) > 0)
            return $inputItem->getValue() == $this->getAttributes()[0];
        return false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not equal to %2$s';
    }

}
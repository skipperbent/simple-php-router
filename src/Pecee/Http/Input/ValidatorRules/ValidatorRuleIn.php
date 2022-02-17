<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleIn extends InputValidatorRule
{

    protected $tag = 'in';
    protected $requires = array('required');

    public function validate(IInputItem $inputItem): bool
    {
        if(sizeof($this->getAttributes()) > 0)
            return in_array($inputItem->getValue(), $this->getAttributes());
        return false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not in the provided data';
    }

}
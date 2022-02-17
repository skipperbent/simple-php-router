<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleMatches extends InputValidatorRule
{

    protected $tag = 'matches';
    protected $requires = array('required');

    public function validate(IInputItem $inputItem): bool
    {
        if(sizeof($this->getAttributes()) > 0)
            return preg_match($this->getAttributes()[0], $inputItem->getValue());
        return false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s do not match RegEx';
    }

}
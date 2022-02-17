<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleCustom extends InputValidatorRule
{

    protected $tag = 'custom';

    public function validate(IInputItem $inputItem): bool
    {
        if(sizeof($this->getAttributes()) > 0 && is_callable($this->getAttributes()[0]))
            return $this->getAttributes()[0]($inputItem);
        return false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not valid due to a custom rule';
    }

}
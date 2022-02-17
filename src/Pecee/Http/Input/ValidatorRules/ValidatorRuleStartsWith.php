<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleStartsWith extends InputValidatorRule
{

    protected $tag = 'starts_with';

    public function validate(IInputItem $inputItem): bool
    {
        $value = strval($inputItem->getValue());
        foreach ($this->getAttributes() as $attribute) {
            if(strncmp($value, $attribute, strlen($attribute)) === 0)
                return true;
        }

        return false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s must start with %s';
    }

}
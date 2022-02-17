<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\Exceptions\InputValidationException;
use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleEndsWith extends InputValidatorRule
{

    protected $tag = 'ends_with';

    public function validate(IInputItem $inputItem): bool
    {
        if (!is_string($inputItem->getValue()) && !is_numeric($inputItem->getValue()))
            throw new InputValidationException('The input %s must be a string or number.');

        $value = strval($inputItem->getValue());
        foreach ($this->getAttributes() as $attribute) {
            $expectedPosition = strlen($value) - strlen($attribute) - 1;
            if (strpos($value, $attribute) === $expectedPosition)
                return true;
        }

        return false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s must end with %s';
    }

}
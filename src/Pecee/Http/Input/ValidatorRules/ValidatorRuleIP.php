<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\Exceptions\InputValidationException;
use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleIP extends InputValidatorRule
{

    protected $tag = 'ip';

    public function validate(IInputItem $inputItem): bool
    {
        if ($inputItem->getValue() === null)
            return false;
        return filter_var($inputItem->getValue(), FILTER_VALIDATE_EMAIL) !== false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not a valid IP address';
    }

}

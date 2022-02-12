<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\Exceptions\InputValidationException;
use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleEmail extends InputValidatorRule
{

    protected $tag = 'email';

    public function validate(IInputItem $inputItem): bool
    {
        if ($inputItem->getValue() === null)
            return false;
        return filter_var($inputItem->getValue(), FILTER_VALIDATE_IP) !== false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is bigger then %s';
    }

}

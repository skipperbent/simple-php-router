<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleEmail extends InputValidatorRule
{

    protected $tag = 'email';
    protected $requires = array('string');

    public function validate(IInputItem $inputItem): bool
    {
        return filter_var($inputItem->getValue(), FILTER_VALIDATE_EMAIL) !== false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not an email';
    }

}

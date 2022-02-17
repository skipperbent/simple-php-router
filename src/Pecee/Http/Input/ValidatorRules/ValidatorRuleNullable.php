<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleNullable extends InputValidatorRule
{

    protected $tag = 'nullable';

    public function validate(IInputItem $inputItem): bool
    {
        return true;
    }

    public function getErrorMessage(): string
    {
        return '';
    }

}
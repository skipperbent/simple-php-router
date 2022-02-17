<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleIP extends InputValidatorRule
{

    protected $tag = 'ip';
    protected $requires = array('string');

    public function validate(IInputItem $inputItem): bool
    {
        return filter_var($inputItem->getValue(), FILTER_VALIDATE_IP) !== false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not a valid IP address';
    }

}

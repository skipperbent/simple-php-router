<?php

namespace Dummy\InputValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleCustomTest extends InputValidatorRule{

    protected $tag = 'customTest';

    /**
     * @param IInputItem $inputItem
     * @return bool
     */
    public function validate(IInputItem $inputItem): bool{
        return $inputItem->getValue() == 'customValue';
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string{
        return 'The input %s is not correct!';
    }

}
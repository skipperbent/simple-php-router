<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleMax extends InputValidatorRule{

    protected $tag = 'max';

    public function getMax(): int{
        if(sizeof($this->getAttributes()) > 0){
            return intval($this->getAttributes()[0]);
        }
        return 0;
    }

    public function validate(IInputItem $inputItem): bool{
        if($inputItem->getValue() === null)
            return false;
        return strlen($inputItem->getValue()) <= $this->getMax();
    }

    public function getErrorMessage(): string{
        return 'The Input %s is bigger then %s';
    }

}
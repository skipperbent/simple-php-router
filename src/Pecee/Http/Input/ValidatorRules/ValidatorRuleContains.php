<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleContains extends InputValidatorRule
{

    protected $tag = 'contains';
    protected $requires = array('string', 'array');

    /**
     * @param array $value
     * @return bool
     */
    private function isAssociativeArray(array $value): bool
    {
        if (array() === $value) return false;
        return array_keys($value) !== range(0, count($value) - 1);
    }

    public function validate(IInputItem $inputItem): bool
    {
        if(is_string($inputItem->getValue())){
            foreach($this->getAttributes() as $attribute){
                if(strpos($inputItem->getValue(), $attribute) !== false)
                    return true;
            }
            return false;
        }
        if(is_array($inputItem->getValue())){
            if($this->isAssociativeArray($inputItem->getValue())){
                foreach($this->getAttributes() as $attribute){
                    if(array_search($attribute, $inputItem->getValue()) !== false)
                        return true;
                }
            }else{
                foreach($this->getAttributes() as $attribute){
                    if(in_array($attribute, $inputItem->getValue()))
                        return true;
                }
            }
            return false;
        }

        return false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s does not contain %2$s';
    }

}
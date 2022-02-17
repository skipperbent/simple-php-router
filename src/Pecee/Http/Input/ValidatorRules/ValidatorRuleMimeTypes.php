<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputFile;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleMimeTypes extends InputValidatorRule
{

    protected $tag = 'mime_types';

    public function validate(IInputItem $inputItem): bool
    {
        if (sizeof($this->getAttributes()) > 0) {
            if(is_a($inputItem, InputFile::class)){
                return $this->getAttributes()[0] === $inputItem->getMime();
            }
        }
        return false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not of type string';
    }

}
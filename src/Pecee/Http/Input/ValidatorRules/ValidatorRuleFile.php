<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputFile;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleFile extends InputValidatorRule
{

    protected $tag = 'file';

    public function validate(IInputItem $inputItem): bool
    {
        return is_a($inputItem, InputFile::class);
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not of type string';
    }

}
<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputFile;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleSize extends InputValidatorRule
{

    protected $tag = 'size';

    public function getSize(): int
    {
        if (sizeof($this->getAttributes()) > 0) {
            return is_int($this->getAttributes()[0]) ? intval($this->getAttributes()[0]) : floatval($this->getAttributes()[0]);
        }
        return 0;
    }

    public function getNumber($input)
    {
        if (is_a($input, InputFile::class))
            return intval($input->getSize()) / 1024; // Size in Kb
        if (is_array($input))
            return count($input);
        if (is_numeric($input))
            return is_int($input) ? intval($input) : floatval($input);

        return strlen($input);
    }

    public function validate(IInputItem $inputItem): bool
    {
        if ($inputItem->getValue() === null)
            return false;
        return $this->getNumber($inputItem) === $this->getSize();
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s has more than %s characteres';
    }

}
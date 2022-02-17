<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputFile;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleSize extends InputValidatorRule
{

    protected $tag = 'size';
    protected $requires = array('file', 'array', 'numeric', 'string');

    public function getSize(): int
    {
        if (sizeof($this->getAttributes()) > 0) {
            return is_int($this->getAttributes()[0]) ? intval($this->getAttributes()[0]) : floatval($this->getAttributes()[0]);
        }
        return 0;
    }

    /**
     * @param IInputItem $input
     * @return float|int|null
     */
    public function getNumber(IInputItem $input)
    {
        if (is_a($input, InputFile::class))
            return intval($input->getSize()) / 1024; // Size in Kb
        $input_value = $input->getValue();
        if (is_array($input_value))
            return count($input_value);
        if (is_numeric($input_value))
            return is_int($input_value) ? $input_value : floatval($input_value);
        if(is_string($input_value))
            return strlen($input_value);
        return null;
    }

    public function validate(IInputItem $inputItem): bool
    {
        return $this->getNumber($inputItem) === $this->getSize();
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s has more than %s characteres';
    }

}
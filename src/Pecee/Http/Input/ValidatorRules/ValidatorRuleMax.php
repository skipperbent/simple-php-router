<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\Exceptions\InputValidationException;
use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputFile;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleMax extends InputValidatorRule
{

    protected $tag = 'max';

    /**
     * @return float|int
     */
    private function getMax()
    {
        if (sizeof($this->getAttributes()) > 0) {
            return is_int($this->getAttributes()[0]) ? intval($this->getAttributes()[0]) : floatval($this->getAttributes()[0]);
        }
        return 0;
    }

    /**
     * @param $input
     * @return float|int
     */
    private function getNumber($input)
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
        return $this->getNumber($inputItem) <= $this->getMax();
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is too big';
    }

}
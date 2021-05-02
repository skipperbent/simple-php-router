<?php

namespace Pecee\Http\Input;

use Pecee\Http\Exceptions\InputValidationException;
use Pecee\SimpleRouter\SimpleRouter;

class InputValidator
{

    /**
     * @var InputItem $inputItem
     */
    private $inputItem;
    /**
     * @var mixed $value
     */
    private $value;
    /**
     * @var bool $valid
     */
    private $valid = true;
    /**
     * @var array $errors
     */
    private $errors = array();

    /**
     * InputValidator constructor.
     * @param InputItem $inputItem
     */
    public function __construct($inputItem)
    {
        $this->inputItem = $inputItem;
        $this->value = $inputItem->getValue();
    }

    /**
     * @return InputItem
     */
    private function getInputItem()
    {
        return $this->inputItem;
    }

    /**
     * @return mixed
     */
    private function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->valid;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param $array
     * @return int|string|null
     */
    protected function _array_key_last($array)
    {
        return key(array_slice($array, -1));
    }

    /**
     * @param $array
     * @return int|string|null
     */
    protected function _array_key_first($array)
    {
        return key(array_slice($array, 0));
    }

    /**
     * @return true|string
     */
    protected function _isNotNull()
    {
        return $this->getValue() !== null ? true : 'This Input is null';
    }

    /**
     * @return static
     */
    public function isNotNull(): self
    {
        return $this->check(function (){
            return $this->_isNotNull();
        });
    }

    /**
     * @return true|string
     */
    protected function _isNull()
    {
        return $this->getValue() === null ? true : 'This Input is not null';
    }

    /**
     * @return static
     */
    public function isNull(): self
    {
        return $this->check(function (){
            return $this->_isNull();
        });
    }

    /**
     * @return true|string
     */
    protected function _require()
    {
        return $this->_isNotNull() === true ? true : 'This Input is null';
    }

    /**
     * @return static
     */
    public function require(): self
    {
        return $this->check(function (){
            return $this->_require();
        });
    }

    /**
     * @param string $pattern
     * @return true|string
     */
    protected function _matches(string $pattern)
    {
        return preg_match($pattern, $this->getValue()) ? true : 'This Input doesn\'t match the pattern';
    }

    /**
     * @param string $pattern
     * @return static
     */
    public function matches(string $pattern)
    {
        return $this->check(function () use ($pattern){
            return $this->_matches($pattern);
        });
    }

    /**
     * @param bool $forceType
     * @return true|string
     */
    protected function _isBoolean(bool $forceType = false)
    {
        if(!is_bool($this->getValue())){
            if(!$forceType && is_string($this->getValue())){
                if(filter_var($this->getValue(), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null){
                    return true;
                }
            }
        }else
            return true;
        return 'This input isn\'t a boolean';
    }

    /**
     * @param bool $forceType
     * @return static
     */
    public function isBoolean(bool $forceType = false): self
    {
        return $this->check(function () use ($forceType){
            return $this->_isBoolean($forceType);
        });
    }

    /**
     * @param bool $forceType
     * @return true|string
     */
    protected function _isInteger(bool $forceType = false)
    {
        if(!is_int($this->getValue())){
            if(!$forceType && is_string($this->getValue())){
                if(is_numeric($this->getValue()) && !$this->_contains('.')){
                    return true;
                }
            }
        }else
            return true;
        return 'This Input isn\'t an Integer';
    }

    /**
     * @param bool $forceType
     * @return static
     */
    public function isInteger(bool $forceType = false): self
    {
        return $this->check(function () use ($forceType){
            return $this->_isInteger($forceType);
        });
    }

    /**
     * @param bool $forceType
     * @return true|string
     */
    protected function _isFloat(bool $forceType = false)
    {
        if(!is_float($this->getValue())){
            if(!$forceType && (is_string($this->getValue()) || is_int($this->getValue()))){
                if(is_numeric($this->getValue())){
                    return true;
                }
            }
        }else
            return true;
        return 'This Input isn\'t a float';
    }

    /**
     * @param bool $forceType
     * @return static
     */
    public function isFloat(bool $forceType = false): self
    {
        return $this->check(function () use ($forceType){
            return $this->_isFloat($forceType);
        });
    }

    /**
     * @return true|string
     */
    protected function _isArray()
    {
        return is_array($this->getValue()) ? true : 'This Input isn\'t an array';
    }

    /**
     * @return static
     */
    public function isArray(): self
    {
        return $this->check(function (){
            return $this->_isArray();
        });
    }

    /**
     * @return true|string
     */
    protected function _isAssociativeArray()
    {
        //TODO this this really an indicator? if (array() === $this->getValue()) return false;
        return array_keys($this->getValue()) !== range(0, count($this->getValue()) - 1) ? true : 'This Input isn\'t an associative array';
    }

    /**
     * @return static
     */
    public function isAssociativeArray(): self
    {
        return $this->check(function (){
            return $this->_isAssociativeArray();
        });
    }

    /**
     * @return true|string
     */
    protected function _isSequentialArray()
    {
        return $this->_isAssociativeArray() !== true ? true : 'This Input isn\'t a sequential array';
    }

    /**
     * @return static
     */
    public function isSequentialArray(): self
    {
        return $this->check(function (){
            return $this->_isSequentialArray();
        });
    }

    /**
     * @return true|string
     */
    protected function _isString()
    {
        return is_string($this->getValue()) ? true : 'This Input isn\'t a string';
    }

    /**
     * @return static
     */
    public function isString(): self
    {
        return $this->check(function (){
            return $this->_isString();
        });
    }

    /**
     * @param int $length
     * @return true|string
     */
    protected function _maxLength(int $length)
    {
        if($this->_isArray() === true){
            return sizeof($this->getValue()) <= $length ? true : 'The size of this array is bigger then the maximal length';
        }
        if($this->_isString() === true){
            return strlen($this->getValue()) <= $length ? true : 'This string is longer then the maximal length';
        }
        if($this->_isInteger(false) === true){
            return strlen(strval($this->getValue())) <= $length ? true : 'The string length of this integer is longer then the maximal length';
        }
        return 'Cannot check the maximal length of this Input';
    }

    /**
     * @param int $length
     * @return static
     */
    public function maxLength(int $length): self
    {
        return $this->check(function () use ($length){
            return $this->_maxLength($length);
        });
    }

    /**
     * @param int $length
     * @return true|string
     */
    protected function _minLength(int $length)
    {
        if($this->_isArray() === true){
            return sizeof($this->getValue()) >= $length ? true : 'The size of this array is smaller then the minimal length';
        }
        if($this->_isString() === true){
            return strlen($this->getValue()) >= $length ? true : 'This string is shorter then the minimal length';
        }
        if($this->_isInteger(false) === true){
            return strlen(strval($this->getValue())) >= $length ? true : 'The string length of this integer is shorter then the minimal length';
        }
        return 'Cannot check the minimal length of this Input';
    }

    /**
     * @param int $length
     * @return static
     */
    public function minLength(int $length): self
    {
        return $this->check(function () use ($length){
            return $this->_minLength($length);
        });
    }

    /**
     * @param int $max
     * @return true|string
     */
    protected function _max(int $max)
    {
        if($this->_isArray() === true){
            return count($this->getValue()) <= $max ? true : 'The size of this array is bigger then the maximal';
        }
        if($this->_isInteger(false) === true){
            return intval($this->getValue()) <= $max ? true : 'This integer is bigger then the maximal';
        }
        if($this->_isFloat(false) === true){
            return floatval($this->getValue()) <= $max ? true : 'This float is bigger then the maximal';
        }
        return 'Cannot check the maximal of this Input';
    }

    /**
     * @param int $max
     * @return static
     */
    public function max(int $max): self
    {
        return $this->check(function () use ($max){
            return $this->_max($max);
        });
    }

    /**
     * @param int $min
     * @return true|string
     */
    protected function _min(int $min)
    {
        if($this->_isArray() === true){
            return count($this->getValue()) >= $min ? true : 'The size of this array is smaller then the minimal';
        }
        if($this->_isInteger(false) === true){
            return intval($this->getValue()) >= $min ? true : 'This integer is smaller then the minimal';
        }
        if($this->_isFloat(false) === true){
            return floatval($this->getValue()) >= $min ? true : 'This float is smaller then the minimal';
        }
        return 'Cannot check the minimal length of this Input';
    }

    /**
     * @param int $min
     * @return static
     */
    public function min(int $min): self
    {
        return $this->check(function () use ($min){
            return $this->_min($min);
        });
    }

    /**
     * @return true|string
     */
    protected function _isEmpty()
    {
        return empty($this->getValue()) ? true : 'This Input is not empty';
    }

    /**
     * @return static
     */
    public function isEmpty(): self
    {
        return $this->check(function (){
            return $this->_isEmpty();
        });
    }

    /**
     * @return true|string
     */
    protected function _isEmail()
    {
        return filter_var($this->getValue(),FILTER_VALIDATE_EMAIL) !== false ? true : 'This Input is not an Email';
    }

    /**
     * @return static
     */
    public function isEmail(): self
    {
        return $this->check(function (){
            return $this->_isEmail();
        });
    }

    /**
     * @return true|string
     */
    protected function _isIP()
    {
        return filter_var($this->getValue(),FILTER_VALIDATE_IP) !== false ? true : 'This Input is not an Ip';
    }

    /**
     * @return static
     */
    public function isIP(): self
    {
        return $this->check(function (){
            return $this->_isIP();
        });
    }

    /**
     * @param mixed $needle
     * @return true|string
     *
     * check for string, integer and array
     */
    protected function _startsWith($needle)
    {
        if($this->_isString() === true || $this->_isInteger() === true){
            $length = strlen(strval($needle));
            return substr(strval($this->getValue()), 0, $length) === strval($needle) ? true : 'This Input don\'t start like that';
        }
        if($this->_isSequentialArray() === true){
            return $this->getValue()[0] === $needle ? true : 'This array don\'t start like that';
        }
        if($this->_isAssociativeArray() === true){
            return $this->_array_key_first($this->getValue()) === $needle ? true : 'This array don\'t start like that';
        }
        return 'Cannot check if this Input starts with something';
    }

    /**
     * @param mixed $needle
     * @return static
     */
    public function startsWith($needle): self
    {
        return $this->check(function () use ($needle){
            return $this->_startsWith($needle);
        });
    }

    /**
     * @param mixed $needle
     * @return true|string
     *
     * check for string, integer and array
     */
    protected function _endsWith($needle)
    {
        if($this->_isString() === true || $this->_isInteger() === true){
            $length = strlen(strval($needle));
            if(!$length) {
                return true;
            }
            return substr(strval($this->getValue()), -$length) === strval($needle) ? true : 'This Input don\'t end like that';
        }
        if($this->_isSequentialArray() === true){
            return $this->getValue()[sizeof($this->getValue())-1] === $needle ? true : 'This array don\'t end like that';
        }
        if($this->_isAssociativeArray() === true){
            return $this->_array_key_last($this->getValue()) === $needle ? true : 'This array don\'t end like that';
        }
        return 'Cannot check if this Input ends with something';
    }

    /**
     * @param mixed $needle
     * @return static
     */
    public function endsWith($needle): self
    {
        return $this->check(function () use ($needle){
            return $this->_endsWith($needle);
        });
    }

    /**
     * @param $needle
     * @return true|string
     */
    protected function _contains($needle)
    {
        if($this->_isString() === true || $this->_isInteger() === true){
            return strpos(strval($this->getValue()), strval($needle)) !== false ? true : 'This Input don\'t contains that';
        }
        if($this->_isSequentialArray() === true){
            return in_array($needle, $this->getValue()) ? true : 'This array don\'t contains that';
        }
        if($this->_isAssociativeArray() === true){
            return array_search($needle, $this->getValue()) ? true : 'This array don\'t contains that';
        }
        return 'Cannot check if this Input contains something';
    }

    /**
     * @param $needle
     * @return static
     */
    public function contains($needle): self
    {
        return $this->check(function () use ($needle){
            return $this->_contains($needle);
        });
    }

    /**
     * @param $key
     * @return true|string
     */
    protected function _arrayKeyExists($key)
    {
        if($this->_isArray() === true){
            return array_key_exists($key, $this->getValue()) ? true : 'This array has no key like that';
        }
        return 'Cannot check if a array key exists on a non array Input';
    }

    /**
     * @param $key
     * @return static
     */
    public function arrayKeyExists($key): self
    {
        return $this->check(function () use ($key){
            return $this->_arrayKeyExists($key);
        });
    }

    /**
     * @param callable $function
     * @return static
     */
    protected function check(callable $function): self
    {
        $callback = $function();
        if($callback !== true){
            $this->valid = false;
            $index = $this->getInputItem()->getIndex();
            if(SimpleRouter::router()->isValidationErrors())
                throw new InputValidationException('Failed to validate Input: ' . $index, $index);
            $this->errors[] = $callback;
        }
        return $this;
    }
}
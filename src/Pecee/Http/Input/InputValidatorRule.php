<?php

namespace Pecee\Http\Input;

abstract class InputValidatorRule implements IInputValidatorRule{

    /**
     * @var string $tag
     */
    protected $tag = null;
    /**
     * @var array $attributes
     */
    protected $attributes;
    /**
     * @var array $requires
     */
    protected $requires = array();

    /**
     * @param ...$attributes
     * @return InputValidatorRule
     */
    public static function make(...$attributes): InputValidatorRule{
        return new static(...$attributes);
    }

    /**
     *
     */
    public function __construct(...$attributes){
        $this->attributes = $attributes;
    }

    /**
     * @return string
     */
    public function getTag(): string{
        return $this->tag;
    }

    /**
     * @return array
     */
    public function getAttributes(): array{
        return $this->attributes;
    }

    /**
     * @return array
     */
    public function getRequiredRules(): array{
        return $this->requires;
    }

    /**
     * @param IInputItem $inputItem
     * @return bool
     */
    public function validate(IInputItem $inputItem): bool{
        return true;
    }

    /**
     * @link https://www.php.net/manual/de/function.sprintf.php
     * The error message is formatted using sprintf.
     * The first argument is the key of the input.
     * All following arguments are the attributes passed to the rule.
     * You can access the first attribute by using "%2$s" where
     * - %2 is the number of the argument (first argument + input key)
     * - $s is the type of the argument (attributes and input key are always a string)
     *
     * @return string
     */
    public function getErrorMessage(): string{
        return 'Error validating Input %s';
    }

    /**
     * @param string $key
     * @return string
     */
    public function formatErrorMessage(string $key): string{
        return sprintf($this->getErrorMessage(), $key, ...$this->getAttributes());
    }

}
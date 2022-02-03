<?php

namespace Pecee\Http\Input;

abstract class InputValidatorRule implements IInputValidatorRule{

    protected $tag = null;
    protected $attributes;

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
     * @param IInputItem $inputItem
     * @return bool
     */
    public function validate(IInputItem $inputItem): bool{
        return true;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string{
        return 'Error validating Input %s';
    }

}
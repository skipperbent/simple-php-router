<?php

namespace Pecee\Http\Input;

interface IInputValidatorRule{

    public static function make(...$attributes): InputValidatorRule;

    public function __construct(...$attributes);

    public function getTag(): string;

    public function getAttributes(): array;

    public function validate(IInputItem $inputItem);
}
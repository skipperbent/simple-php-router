<?php

namespace Pecee\Http\Input;

interface IInputError
{

    public function __construct(string $message, int $code = 0);

    public function getMessage(): string;

    public function setMessage();
}
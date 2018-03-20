<?php

namespace Pecee\Http\Input;

interface IInputItem
{

    public function getIndex(): string;

    public function setIndex($index): self;

    public function getName(): string;

    public function setName($name): self;

    public function getValue(): string;

    public function setValue($value): self;

    public function __toString();

}
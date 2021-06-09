<?php

namespace Pecee\Http\Input;

interface IInputItem
{

    public function getIndex(): string;

    public function setIndex(string $index): self;

    public function getName(): ?string;

    public function setName(string $name): self;

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @param mixed $value
     */
    public function setValue($value): self;

    public function __toString(): string;

}
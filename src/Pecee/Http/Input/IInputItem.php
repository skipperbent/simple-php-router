<?php

namespace Pecee\Http\Input;

/**
 * Interface IInputItem
 *
 * @package Pecee\Http\Input
 */
interface IInputItem
{
    /**
     * @return string
     */
    public function getIndex(): string;

    /**
     * @param string $index
     * @return IInputItem
     */
    public function setIndex(string $index): self;

    /**
     * @return null|string
     */
    public function getName(): ?string;

    /**
     * @param string $name
     * @return IInputItem
     */
    public function setName(string $name): self;

    /**
     * @return null|string
     */
    public function getValue(): ?string;

    /**
     * @param string $value
     * @return IInputItem
     */
    public function setValue(string $value): self;

    /**
     * @return string
     */
    public function __toString(): string;

}
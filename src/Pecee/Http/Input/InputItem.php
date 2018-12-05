<?php

namespace Pecee\Http\Input;

/**
 * Class InputItem
 *
 * @package Pecee\Http\Input
 */
class InputItem implements IInputItem
{
    public $index;
    public $name;
    public $value;

    /**
     * InputItem constructor.
     * @param string $index
     * @param null|string $value
     */
    public function __construct(string $index, ?string $value = null)
    {
        $this->index = $index;
        $this->value = $value;
        // Make the name human friendly, by replace _ with space
        $this->name = ucfirst(str_replace('_', ' ', strtolower($this->index)));
    }

    /**
     * @return string
     */
    public function getIndex(): string
    {
        return $this->index;
    }

    /**
     * @param string $index
     * @return IInputItem
     */
    public function setIndex(string $index): IInputItem
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return IInputItem
     */
    public function setName(string $name): IInputItem
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return IInputItem
     */
    public function setValue(string $value): IInputItem
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->value;
    }

}
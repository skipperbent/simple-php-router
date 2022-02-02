<?php

namespace Pecee\Http\Input;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

class InputItem implements ArrayAccess, IInputItem, IteratorAggregate
{
    public $index;
    public $name;
    public $value;

    public function __construct(string $index, $value = null)
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
     * Set input name
     * @param string $name
     * @return static
     */
    public function setName(string $name): IInputItem
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set input value
     * @param mixed $value
     * @return static
     */
    public function setValue($value): IInputItem
    {
        $this->value = $value;

        return $this;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->value[$offset]);
    }

    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset) === true) {
            return $this->value[$offset];
        }

        return null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->value[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->value[$offset]);
    }

    public function __toString(): string
    {
        $value = $this->getValue();

        return (is_array($value) === true) ? json_encode($value) : $value;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getValue());
    }
}
<?php

namespace Pecee\Http\Input;

use Exception;
use Traversable;

class InputItem implements IInputItem, \IteratorAggregate
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
        /*if(is_array($this->value) === true) {
            $output = [];
            foreach($this->value as $key => $val) {
                $output[$key] = $val->getValue();
            }

            return $output;
        }*/

        return $this->value;
    }

    /**
     * Set input value
     * @param string $value
     * @return static
     */
    public function setValue(string $value): IInputItem
    {
        $this->value = $value;

        return $this;
    }

    public function __toString(): string
    {
        $value = $this->getValue();
        return (\is_array($value) === true) ? json_encode($value) : $value;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->getValue());
    }
}
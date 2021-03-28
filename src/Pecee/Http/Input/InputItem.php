<?php

namespace Pecee\Http\Input;

class InputItem implements IInputItem, \IteratorAggregate
{
    public $index;
    public $name;
    public $value;

    private $validator = null;

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

    /**
     * @param bool $forceNew
     * @return InputValidator
     */
    public function validate(bool $forceNew = false): InputValidator
    {
        if($this->validator === null || $forceNew)
            $this->validator = new InputValidator($this);
        return $this->validator;
    }

    /**
     * @return bool|null
     */
    public function parseBoolean(): ?bool
    {
        if($this->getValue() === "true" || $this->getValue() === "false" || $this->getValue() == "1" || $this->getValue() == "0"){
            return (bool) $this->getValue();
        }else{
            return null;
        }
    }

    /**
     * @return int|null
     */
    public function parseInteger(): ?int
    {
        if(is_numeric($this->getValue())){
            return (int) $this->getValue();
        }else{
            return null;
        }
    }

    /**
     * @return float|null
     */
    public function parseFloat(): ?float
    {
        if(is_numeric($this->getValue())){
            return (float) $this->getValue();
        }else{
            return null;
        }
    }

    /**
     * @return string
     */
    public function parseString(): string
    {
        return (string) $this->getValue();
    }

    public function __toString(): string
    {
        $value = $this->getValue();
        return (\is_array($value) === true) ? json_encode($value) : $value;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->getValue());
    }
}
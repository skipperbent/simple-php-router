<?php
namespace Pecee\Http\Input;

class InputItem {

    protected $index;
    protected $name;
    protected $value;

    public function __construct($index, $value) {
        $this->index = $index;
        $this->value = $value;

        // Make the name human friendly, by replace _ with space
        $this->name = ucfirst(str_replace('_', ' ', $this->index));
    }

    /**
     * @return array
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getIndex() {
        return $this->index;
    }

    /**
     * Set input name
     * @param string $name
     * @return static $this
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function __toString() {
        return (string)$this->getValue();
    }

}
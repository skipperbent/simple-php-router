<?php
namespace Pecee\Http\Input;

class InputCollection implements \IteratorAggregate {

    protected $data = array();

    /**
     * Search for input element matching index.
     * Useful for searching for finding items where $index doesn't contain form name.
     *
     * @param string $index
     * @param string|null $defaultValue
     * @return mixed
     */
    public function findFirst($index, $defaultValue = null) {
        if(count($this->data)) {

            if(isset($this->data[$index])) {
                return $this->data[$index];
            }

            foreach($this->data as $key => $value) {
                if(strtolower($index) === strtolower($key)) {
                    return $value;
                }
            }
        }

        return $defaultValue;
    }

    /**
     * @param $index
     * @throws \InvalidArgumentException
     * @return InputItem
     */
    public function __get($index) {
        $item = $this->findFirst($index);
        // Ensure that item are always available
        if($item === null) {
            $this->data[$index] = new InputItem($index, null);
            return $this->data[$index];
        }

        return $item;
    }

    public function __set($index, $value) {
        $this->data[$index] = $value;
    }

    public function getData() {
        return $this->data;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator() {
        return new \ArrayIterator($this->data);
    }

}
<?php
namespace Pecee\Http\Input;

class InputCollection implements \IteratorAggregate
{
	protected $data = [];

	/**
	 * Search for input element matching index.
	 *
	 * @param string $index
	 * @param string|null $default
	 * @return InputItem|mixed
	 */
	public function findFirst($index, $default = null)
	{
		if (count($this->data) > 0) {

			if (isset($this->data[$index])) {
				return $this->data[$index];
			}

			foreach ($this->data as $key => $input) {
				if (strtolower($index) === strtolower($key)) {
					return $input;
				}
			}
		}

		return $default;
	}

	/**
	 * Get input element value matching index
	 *
	 * @param string $index
	 * @param string|null $default
	 * @return string|null
	 */
	public function get($index, $default = null)
	{
		$input = $this->findFirst($index);

		if ($input !== null && trim($input->getValue()) !== '') {
			return $input->getValue();
		}

		return $default;
	}

	/**
	 * @param string $index
	 * @throws \InvalidArgumentException
	 * @return InputItem
	 */
	public function __get($index)
	{
		$item = $this->findFirst($index);
		// Ensure that item are always available
		if ($item === null) {
			$this->data[$index] = new InputItem($index, null);

			return $this->data[$index];
		}

		return $item;
	}

	public function __set($index, $value)
	{
		$this->data[$index] = $value;
	}

	public function getData()
	{
		return $this->data;
	}

	/**
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return \Traversable An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 * @since 5.0.0
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->data);
	}

}
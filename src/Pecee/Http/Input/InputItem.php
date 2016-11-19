<?php
namespace Pecee\Http\Input;

class InputItem
{
	public $index;
	public $name;
	public $value;

	public function __construct($index, $value = null)
	{
		$this->index = $index;
		$this->value = $value;

		// Make the name human friendly, by replace _ with space
		$this->name = ucfirst(str_replace('_', ' ', $this->index));
	}

	/**
	 * @return array
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return array
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return string
	 */
	public function getIndex()
	{
		return $this->index;
	}

	/**
	 * Set input name
	 * @param string $name
	 * @return static $this
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * Set input value
	 * @param string $value
	 * @return static $this
	 */
	public function setValue($value)
	{
		$this->value = $value;
		return $this;
	}

	public function __toString()
	{
		return (string)$this->value;
	}

}
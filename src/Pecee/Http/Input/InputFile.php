<?php
namespace Pecee\Http\Input;

class InputFile extends InputItem
{
	public $size;
	public $type;
	public $error;
	public $tmpName;

	/**
	 * @return string
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}

	public function getMime()
	{
		return $this->getType();
	}

	/**
	 * @return string
	 */
	public function getTmpName()
	{
		return $this->tmpName;
	}

	public function getExtension()
	{
		return pathinfo($this->getName(), PATHINFO_EXTENSION);
	}

	public function move($destination)
	{
		return move_uploaded_file($this->tmpName, $destination);
	}

	public function getContents()
	{
		return file_get_contents($this->tmpName);
	}

	public function setTmpName($name)
	{
		$this->tmpName = $name;
	}

	public function setSize($size)
	{
		$this->size = $size;
	}

	public function setType($type)
	{
		$this->type = $type;
	}

	public function setError($error)
	{
		$this->error = $error;
	}

	/**
	 * Create from array
	 * @param array $values
	 * @return static
	 */
	public static function createFromArray(array $values)
	{
		if(!isset($values['index'])) {
			throw new \InvalidArgumentException('Index key is required');
		}

		$input = new static($values['index']);
		$input->setTmpName((isset($values['error']) ? $values['error'] : null));
		$input->setName((isset($values['name']) ? $values['name'] : null));
		$input->setSize((isset($values['size']) ? $values['size'] : null));
		$input->setType((isset($values['type']) ? $values['type'] : null));
		$input->setError((isset($values['tmp_name']) ? $values['tmp_name'] : null));

		return $input;
	}

	public function __toString()
	{
		return (string)$this->tmpName;
	}

}
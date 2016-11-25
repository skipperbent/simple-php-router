<?php
namespace Pecee\Http\Input;

class InputFile implements IInputItem
{
    public $index;
    public $name;
    public $size;
    public $type;
    public $error;
    public $tmpName;

    public function __construct($index)
    {
        $this->index = $index;

        // Make the name human friendly, by replace _ with space
        $this->name = ucfirst(str_replace('_', ' ', $this->index));
    }

    /**
     * @return string
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

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

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set file temp. name
     * @param string $name
     * @return static $this
     */
    public function setTmpName($name)
    {
        $this->tmpName = $name;

        return $this;
    }

    /**
     * Set file size
     * @param int $size
     * @return static $this
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Set type
     * @param string $type
     * @return static $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set error
     * @param int $error
     * @return static $this
     */
    public function setError($error)
    {
        $this->error = (int)$error;

        return $this;
    }

    public function getValue()
    {
        return $this->getTmpName();
    }

    public function hasError()
    {
        return ($this->getError() !== 0);
    }

    /**
     * Create from array
     * @param array $values
     * @return static
     */
    public static function createFromArray(array $values)
    {
        if (!isset($values['index'])) {
            throw new \InvalidArgumentException('Index key is required');
        }

        $input = new static($values['index']);
        $input->setError(isset($values['error']) ? $values['error'] : null);
        $input->setName(isset($values['name']) ? $values['name'] : null);
        $input->setSize(isset($values['size']) ? $values['size'] : null);
        $input->setType(isset($values['type']) ? $values['type'] : null);
        $input->setTmpName(isset($values['tmp_name']) ? $values['tmp_name'] : null);

        return $input;
    }

    public function toArray()
    {
        return [
            'tmp_name' => $this->tmpName,
            'type'     => $this->type,
            'size'     => $this->size,
            'name'     => $this->name,
            'error'    => $this->error,
        ];
    }

    public function __toString()
    {
        return $this->getValue();
    }
}
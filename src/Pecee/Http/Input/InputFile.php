<?php

namespace Pecee\Http\Input;

use Pecee\Exceptions\InvalidArgumentException;

class InputFile implements IInputItem
{
    public $index;
    public $name;
    public $filename;
    public $size;
    public $type;
    public $errors;
    public $tmpName;

    public function __construct(string $index)
    {
        $this->index = $index;

        $this->errors = 0;

        // Make the name human friendly, by replace _ with space
        $this->name = ucfirst(str_replace('_', ' ', strtolower($this->index)));
    }

    /**
     * Create from array
     *
     * @param array $values
     * @throws InvalidArgumentException
     * @return static
     */
    public static function createFromArray(array $values): self
    {
        if (isset($values['index']) === false) {
            throw new InvalidArgumentException('Index key is required');
        }

        /* Easy way of ensuring that all indexes-are set and not filling the screen with isset() */

        $values += [
            'tmp_name' => null,
            'type'     => null,
            'size'     => null,
            'name'     => null,
            'error'    => null,
        ];

        return (new static($values['index']))
            ->setSize((int)$values['size'])
            ->setError((int)$values['error'])
            ->setType($values['type'])
            ->setTmpName($values['tmp_name'])
            ->setFilename($values['name']);

    }

    /**
     * @return string
     */
    public function getIndex(): string
    {
        return $this->index;
    }

    /**
     * Set input index
     * @param string $index
     * @return static
     */
    public function setIndex(string $index): IInputItem
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @return string
     */
    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * Set file size
     * @param int $size
     * @return static
     */
    public function setSize(int $size): IInputItem
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get mime-type of file
     * @return string
     */
    public function getMime(): string
    {
        return $this->getType();
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set type
     * @param string $type
     * @return static
     */
    public function setType(string $type): IInputItem
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Returns extension without "."
     *
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
    }

    /**
     * Get human friendly name
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set human friendly name.
     * Useful for adding validation etc.
     *
     * @param string $name
     * @return static
     */
    public function setName(string $name): IInputItem
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set filename
     *
     * @param string $name
     * @return static
     */
    public function setFilename($name): IInputItem
    {
        $this->filename = $name;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string mixed
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * Move the uploaded temporary file to it's new home
     *
     * @param string $destination
     * @return bool
     */
    public function move($destination): bool
    {
        return move_uploaded_file($this->tmpName, $destination);
    }

    /**
     * Get file contents
     *
     * @return string
     */
    public function getContents(): string
    {
        return file_get_contents($this->tmpName);
    }

    /**
     * Return true if an upload error occurred.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return ($this->getError() !== 0);
    }

    /**
     * Get upload-error code.
     *
     * @return int
     */
    public function getError(): int
    {
        return (int)$this->errors;
    }

    /**
     * Set error
     *
     * @param int $error
     * @return static
     */
    public function setError($error): IInputItem
    {
        $this->errors = (int)$error;

        return $this;
    }

    /**
     * @return string
     */
    public function getTmpName(): string
    {
        return $this->tmpName;
    }

    /**
     * Set file temp. name
     * @param string $name
     * @return static
     */
    public function setTmpName($name): IInputItem
    {
        $this->tmpName = $name;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getTmpName();
    }

    public function getValue(): ?string
    {
        return $this->getFilename();
    }

    /**
     * @param string $value
     * @return static
     */
    public function setValue(string $value): IInputItem
    {
        $this->filename = $value;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'tmp_name' => $this->tmpName,
            'type'     => $this->type,
            'size'     => $this->size,
            'name'     => $this->name,
            'error'    => $this->errors,
            'filename' => $this->filename,
        ];
    }

}
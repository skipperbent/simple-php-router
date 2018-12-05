<?php

namespace Pecee\Http\Input;

use Pecee\Exceptions\InvalidArgumentException;

/**
 * Class InputFile
 *
 * @package Pecee\Http\Input
 */
class InputFile implements IInputItem
{
    public $index;
    public $name;
    public $filename;
    public $size;
    public $type;
    public $errors;
    public $tmpName;

    /**
     * InputFile constructor.
     * @param string $index
     */
    public function __construct(string $index)
    {
        $this->index = $index;
        $this->errors = 0;
        // Make the name human friendly, by replace _ with space
        $this->name = ucfirst(str_replace('_', ' ', strtolower($this->index)));
    }

    /**
     * @param array $values
     * @return InputFile
     */
    public static function createFromArray(array $values): self
    {
        if (isset($values['index']) === false) {
            throw new InvalidArgumentException('Index key is required');
        }
        /* Easy way of ensuring that all indexes-are set and not filling the screen with isset() */
        $values += [
            'tmp_name' => null,
            'type' => null,
            'size' => null,
            'name' => null,
            'error' => null,
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
     * @param string $index
     * @return IInputItem
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
     * @param int $size
     * @return IInputItem
     */
    public function setSize(int $size): IInputItem
    {
        $this->size = $size;

        return $this;
    }

    /**
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
     * @param string $type
     * @return IInputItem
     */
    public function setType(string $type): IInputItem
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
    }

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return IInputItem
     */
    public function setName(string $name): IInputItem
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param $name
     * @return IInputItem
     */
    public function setFilename($name): IInputItem
    {
        $this->filename = $name;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @param $destination
     * @return bool
     */
    public function move($destination): bool
    {
        return move_uploaded_file($this->tmpName, $destination);
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        return file_get_contents($this->tmpName);
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return ($this->getError() !== 0);
    }

    /**
     * @return int
     */
    public function getError(): int
    {
        return (int)$this->errors;
    }

    /**
     * @param $error
     * @return IInputItem
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
     * @param $name
     * @return IInputItem
     */
    public function setTmpName($name): IInputItem
    {
        $this->tmpName = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getTmpName();
    }

    /**
     * @return null|string
     */
    public function getValue(): ?string
    {
        return $this->getFilename();
    }

    /**
     * @param string $value
     * @return IInputItem
     */
    public function setValue(string $value): IInputItem
    {
        $this->filename = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'tmp_name' => $this->tmpName,
            'type' => $this->type,
            'size' => $this->size,
            'name' => $this->name,
            'error' => $this->errors,
            'filename' => $this->filename,
        ];
    }
}
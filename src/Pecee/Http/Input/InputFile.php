<?php
namespace Pecee\Http\Input;

class InputFile extends InputItem {

	protected $name;
	protected $size;
	protected $type;
	protected $error;
	protected $tmpName;

	/**
	 * @return string
	 */
	public function getSize() {
		return $this->size;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * @return string
	 */
	public function getTmpName() {
		return $this->tmpName;
	}

    public function getExtension() {
		return pathinfo($this->getName(), PATHINFO_EXTENSION);
    }

	public function move($destination) {
		return move_uploaded_file($this->tmpName, $destination);
	}

	public function getContents() {
		return file_get_contents($this->tmpName);
	}

    public function setTmpName($name) {
        $this->tmpName = $name;
    }

    public function setSize($size) {
        $this->size = $size;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function setError($error) {
        $this->error = $error;
    }

}
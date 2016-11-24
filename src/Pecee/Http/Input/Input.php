<?php
namespace Pecee\Http\Input;

use Pecee\Http\Request;

class Input
{
	/**
	 * @var array
	 */
	public $get = [];

	/**
	 * @var array
	 */
	public $post = [];

	/**
	 * @var array
	 */
	public $file = [];

	/**
	 * @var Request
	 */
	protected $request;

	protected $invalidContentType = false;

	protected $invalidContentTypes = [
		'text/plain',
		'application/x-www-form-urlencoded',
	];

	public function __construct(Request $request)
	{
		$this->request = $request;

		if ($request->getMethod() !== 'get') {

			$requestContentType = $request->getHeader('http-content-type');

			$max = count($this->invalidContentTypes) - 1;

			for ($i = $max; $i >= 0; $i--) {

				$contentType = $this->invalidContentType[$i];

				if (stripos($requestContentType, $contentType) === 0) {
					$this->invalidContentType = true;
					break;
				}
			}
		}

		if ($this->invalidContentType === false) {
			$this->parseInputs();
		}

	}

	protected function parseInputs()
	{
		/* Parse get requests */
		if (count($_GET) > 0) {
			$this->get = $this->handleGetPost($_GET);
		}

		/* Parse post requests */
		$postVars = $_POST;

		if (in_array($this->request->getMethod(), ['put', 'patch', 'delete']) === true) {
			parse_str(file_get_contents('php://input'), $postVars);
		}

		if (count($postVars) > 0) {
			$this->post = $this->handleGetPost($postVars);
		}

		/* Parse get requests */

		if (count($_FILES) > 0) {

			$max = count($_FILES) - 1;
			$keys = array_keys($_FILES);

			for ($i = $max; $i >= 0; $i--) {

				$key = $keys[$i];
				$value = $_FILES[$key];

				// Handle array input
				if (is_array($value['name']) === false) {
					$values['index'] = $key;
					$this->file[$key] = InputFile::createFromArray(array_merge($value, $values));
					continue;
				}

				$subMax = count($value['name']) - 1;
				$keys = array_keys($value['name']);
				$output = [];

				for ($i = $subMax; $i >= 0; $i--) {

					$output[$keys[$i]] = InputFile::createFromArray([
						'index'    => $key,
						'error'    => $value['error'][$keys[$i]],
						'tmp_name' => $value['tmp_name'][$keys[$i]],
						'type'     => $value['type'][$keys[$i]],
						'size'     => $value['size'][$keys[$i]],
						'name'     => $value['name'][$keys[$i]],
					]);

				}

				$this->file[$key] = $output;
			}
		}
	}

	protected function handleGetPost($array)
	{
		$tmp = [];

		$max = count($array) - 1;
		$keys = array_keys($array);

		for ($i = $max; $i >= 0; $i--) {

			$key = $keys[$i];
			$value = $array[$key];

			// Handle array input
			if (is_array($value) === false) {
				$tmp[$key] = new InputItem($key, $value);
				continue;
			}

			$subMax = count($value) - 1;
			$keys = array_keys($value);
			$output = [];

			for ($i = $subMax; $i >= 0; $i--) {
				$output[$keys[$i]] = new InputItem($key, $value[$keys[$i]]);
			}

			$tmp[$key] = $output;
		}

		return $tmp;
	}

	public function findPost($index, $default = null)
	{
		return isset($this->post[$index]) ? $this->post[$index] : $default;
	}

	public function findFile($index, $default = null)
	{
		return isset($this->file[$index]) ? $this->file[$index] : $default;
	}

	public function findGet($index, $default = null)
	{
		return isset($this->get[$index]) ? $this->get[$index] : $default;
	}

	public function getObject($index, $default = null, $method = null)
	{
		$element = null;

		if ($method === null || strtolower($method) === 'get') {
			$element = $this->findGet($index);
		}

		if ($element === null && $method === null || strtolower($method) === 'post') {
			$element = $this->findPost($index);
		}

		if ($element === null && $method === null || strtolower($method) === 'file') {
			$element = $this->findFile($index);
		}

		return ($element === null) ? $default : $element;
	}

	/**
	 * Get input element value matching index
	 *
	 * @param string $index
	 * @param string|null $default
	 * @return InputItem|string
	 */
	public function get($index, $default = null)
	{
		$input = $this->getObject($index, $default);

		if ($input instanceof InputItem) {
			return (trim($input->getValue()) === '') ? $default : $input->getValue();
		}

		return $input;
	}

	public function exists($index)
	{
		return ($this->getObject($index) !== null);
	}

	/**
	 * Get all get/post items
	 * @param array|null $filter Only take items in filter
	 * @return array
	 */
	public function all(array $filter = null)
	{
		if ($this->invalidContentType === true) {
			return [];
		}

		$output = $_POST;

		if ($this->request->getMethod() === 'post') {

			$contents = file_get_contents('php://input');

			if (stripos(trim($contents), '{') === 0) {
				$output = json_decode($contents, true);
				if ($output === false) {
					$output = [];
				}
			}
		}

		$output = array_merge($_GET, $output);

		if ($filter !== null) {
			$output = array_filter($output, function ($key) use ($filter) {
				if (in_array($key, $filter)) {
					return true;
				}

				return false;
			}, ARRAY_FILTER_USE_KEY);
		}

		return $output;
	}

}
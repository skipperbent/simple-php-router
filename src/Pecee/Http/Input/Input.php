<?php
namespace Pecee\Http\Input;

use Pecee\Http\Request;

class Input
{
	/**
	 * @var \Pecee\Http\Input\InputCollection
	 */
	public $get;

	/**
	 * @var \Pecee\Http\Input\InputCollection
	 */
	public $post;

	/**
	 * @var \Pecee\Http\Input\InputCollection
	 */
	public $file;

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

		$this->post = new InputCollection();
		$this->get = new InputCollection();
		$this->file = new InputCollection();

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

			$max = count($_GET) - 1;
			$keys = array_keys($_GET);

			for ($i = $max; $i >= 0; $i--) {

				$key = $keys[$i];
				$value = $_GET[$key];

				$this->get->{$key} = new InputItem($key, $value);
			}

		}

		/* Parse post requests */

		$postVars = $_POST;

		if (in_array($this->request->getMethod(), ['put', 'patch', 'delete']) === true) {
			parse_str(file_get_contents('php://input'), $postVars);
		}

		if (count($postVars) > 0) {

			$max = count($postVars) - 1;
			$keys = array_keys($postVars);

			for ($i = $max; $i >= 0; $i--) {

				$key = $keys[$i];
				$value = $postVars[$key];

				$this->post->{strtolower($key)} = new InputItem($key, $value);
			}

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
					$this->file->{strtolower($key)} = InputFile::createFromArray($values);
					continue;
				}

				$output = new InputCollection();

				foreach ($value['name'] as $k => $val) {

					$output->{$k} = InputFile::createFromArray([
						'index'    => $key,
						'error'    => $value['error'][$k],
						'tmp_name' => $value['tmp_name'][$k],
						'type'     => $value['type'][$k],
						'size'     => $value['size'][$k],
						'name'     => $value['name'][$k],
					]);

				}

				$this->file->{strtolower($key)} = $output;
			}
		}

	}

	public function getObject($index, $default = null)
	{
		$key = (strpos($index, '[') > -1) ? substr($index, strpos($index, '[') + 1, strpos($index, ']') - strlen($index)) : null;
		$index = (strpos($index, '[') > -1) ? substr($index, 0, strpos($index, '[')) : $index;

		$element = $this->get->findFirst($index);

		if ($element !== null) {
			return ($key !== null) ? $element[$key] : $element;
		}

		if ($this->request->getMethod() !== 'get') {

			$element = $this->post->findFirst($index);
			if ($element !== null) {
				return ($key !== null) ? $element[$key] : $element;
			}

			$element = $this->file->findFirst($index);
			if ($element !== null) {
				return ($key !== null) ? $element[$key] : $element;
			}
		}

		return $default;
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
		$item = $this->getObject($index);

		if ($item !== null) {

			if ($item instanceof InputCollection || $item instanceof InputFile) {
				return $item;
			}

			return (!is_array($item->getValue()) && trim($item->getValue()) === '') ? $default : $item->getValue();
		}

		return $default;
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
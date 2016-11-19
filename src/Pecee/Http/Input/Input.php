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

	public function __construct(Request $request)
	{
		$this->request = $request;
		$this->setGet();
		$this->setPost();
		$this->setFile();
	}

	/**
	 * Get all get/post items
	 * @param array|null $filter Only take items in filter
	 * @return array
	 */
	public function all(array $filter = null)
	{
		$output = $_POST;

		if ($this->request->getMethod() === 'post') {

			$contents = file_get_contents('php://input');

			if (stripos(trim($contents), '{') === 0) {
				$output = json_decode($contents, true);
				if ($output === false) {
					$output = array();
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
	 * @param string $index
	 * @param string|null $default
	 * @return string|null
	 */
	public function get($index, $default = null)
	{
		$item = $this->getObject($index);

		if ($item !== null) {

			if ($item instanceof InputCollection || $item instanceof InputFile) {
				return $item;
			}

			return (trim($item->getValue()) === '') ? $default : $item->getValue();
		}

		return $default;
	}

	public function exists($index)
	{
		return ($this->getObject($index) !== null);
	}

	public function setGet()
	{
		$this->get = new InputCollection();

		if (count($_GET) > 0) {
			foreach ($_GET as $key => $get) {
				if (is_array($get) === false) {
					$this->get->{$key} = new InputItem($key, $get);
					continue;
				}

				$output = new InputCollection();

				foreach ($get as $k => $g) {
					$output->{$k} = new InputItem($k, $g);
				}

				$this->get->{$key} = $output;
			}
		}
	}

	public function setPost()
	{
		$this->post = new InputCollection();

		$postVars = $_POST;

		if (in_array($this->request->getMethod(), ['put', 'patch', 'delete']) === true) {
			parse_str(file_get_contents('php://input'), $postVars);
		}

		if (count($postVars) > 0) {

			foreach ($postVars as $key => $post) {
				if (is_array($post) === false) {
					$this->post->{strtolower($key)} = new InputItem($key, $post);
					continue;
				}

				$output = new InputCollection();

				foreach ($post as $k => $p) {
					$output->{$k} = new InputItem($k, $p);
				}

				$this->post->{strtolower($key)} = $output;
			}
		}
	}

	public function setFile()
	{
		$this->file = new InputCollection();

		if (count($_FILES) > 0) {
			foreach ($_FILES as $key => $values) {

				// Handle array input
				if (is_array($values['name']) === false && trim($values['error']) !== '4') {
					$values['index'] = $key;
					$this->file->{strtolower($key)} = InputFile::createFromArray($values);
					continue;
				}

				$output = new InputCollection();

				foreach ($values['name'] as $k => $val) {
					if (trim($val['error'][$k]) !== '4') {
						$output->{$k} = InputFile::createFromArray([
							'index' => $k,
							'error' => $val['error'][$k],
							'tmp_name' => $val['tmp_name'][$k],
							'type' => $val['type'][$k],
							'size' => $val['size'][$k],
							'name' => $val['name'][$k]
						]);
					}
				}

				$this->file->{strtolower($key)} = $output;
			}
		}
	}

}
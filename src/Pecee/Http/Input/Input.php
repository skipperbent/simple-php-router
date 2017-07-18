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

    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->parseInputs();
    }

    public function parseInputs()
    {
        /* Parse get requests */
        if (count($_GET) > 0) {
            $this->get = $this->handleGetPost($_GET);
        }

        /* Parse post requests */
        $postVars = $_POST;

        if (in_array($this->request->getMethod(), ['put', 'patch', 'delete'], false) === true) {
            parse_str(file_get_contents('php://input'), $postVars);
        }

        if (count($postVars) > 0) {
            $this->post = $this->handleGetPost($postVars);
        }

        /* Parse get requests */
        if (count($_FILES) > 0) {
            $this->file = $this->parseFiles();
        }
    }

    public function parseFiles()
    {
        $list = [];

        foreach ((array)$_FILES as $key => $value) {

            // Handle array input
            if (is_array($value['name']) === false) {
                $values['index'] = $key;
                $list[$key] = InputFile::createFromArray(array_merge($value, $values));
                continue;
            }

            $keys = [];

            $files = $this->rearrangeFiles($value['name'], $keys, $value);

            if (isset($list[$key])) {
                $list[$key][] = $files;
            } else {
                $list[$key] = $files;
            }

        }

        return $list;
    }

    protected function rearrangeFiles(array $values, &$index, $original)
    {

        $output = [];

        $getItem = function ($key, $property = 'name') use ($original, $index) {

            $path = $original[$property];

            $fileValues = array_values($index);

            foreach ($fileValues as $i) {
                $path = $path[$i];
            }

            return $path[$key];
        };

        foreach ($values as $key => $value) {

            if (is_array($getItem($key)) === false) {

                $file = InputFile::createFromArray([
                    'index'    => $key,
                    'filename' => $getItem($key),
                    'error'    => $getItem($key, 'error'),
                    'tmp_name' => $getItem($key, 'tmp_name'),
                    'type'     => $getItem($key, 'type'),
                    'size'     => $getItem($key, 'size'),
                ]);

                if (isset($output[$key])) {
                    $output[$key][] = $file;
                } else {
                    $output[$key] = $file;
                }

                continue;
            }

            $index[] = $key;

            $files = $this->rearrangeFiles($value, $index, $original);

            if (isset($output[$key])) {
                $output[$key][] = $files;
            } else {
                $output[$key] = $files;
            }

        }

        return $output;
    }

    protected function handleGetPost(array $array)
    {
        $list = [];

        $max = count($array) - 1;
        $keys = array_keys($array);

        for ($i = $max; $i >= 0; $i--) {

            $key = $keys[$i];
            $value = $array[$key];

            // Handle array input
            if (is_array($value) === false) {
                $list[$key] = new InputItem($key, $value);
                continue;
            }

            $output = $this->handleGetPost($value);

            $list[$key] = $output;
        }

        return $list;
    }

    /**
     * Find post-value by index or return default value.
     *
     * @param string $index
     * @param string|null $defaultValue
     * @return InputItem|string
     */
    public function findPost($index, $defaultValue = null)
    {
        return isset($this->post[$index]) ? $this->post[$index] : $defaultValue;
    }

    /**
     * Find file by index or return default value.
     *
     * @param string $index
     * @param string|null $defaultValue
     * @return InputFile|string
     */
    public function findFile($index, $defaultValue = null)
    {
        return isset($this->file[$index]) ? $this->file[$index] : $defaultValue;
    }

    /**
     * Find parameter/query-string by index or return default value.
     *
     * @param string $index
     * @param string|null $defaultValue
     * @return InputItem|string
     */
    public function findGet($index, $defaultValue = null)
    {
        return isset($this->get[$index]) ? $this->get[$index] : $defaultValue;
    }

    /**
     * Get input object
     *
     * @param string $index
     * @param string|null $defaultValue
     * @param array|string|null $methods
     * @return IInputItem|string
     */
    public function getObject($index, $defaultValue = null, $methods = null)
    {
        if ($methods !== null && is_string($methods) === true) {
            $methods = [$methods];
        }

        $element = null;

        if ($methods === null || in_array('get', $methods)) {
            $element = $this->findGet($index);
        }

        if (($element === null && $methods === null) || ($methods !== null && in_array('post', $methods))) {
            $element = $this->findPost($index);
        }

        if (($element === null && $methods === null) || ($methods !== null && in_array('file', $methods))) {
            $element = $this->findFile($index);
        }

        return ($element !== null) ? $element : $defaultValue;
    }

    /**
     * Get input element value matching index
     *
     * @param string $index
     * @param string|null $defaultValue
     * @param array|string|null $methods
     * @return InputItem|string
     */
    public function get($index, $defaultValue = null, $methods = null)
    {
        $input = $this->getObject($index, $defaultValue, $methods);

        if ($input instanceof InputItem) {
            return (trim($input->getValue()) === '') ? $defaultValue : $input->getValue();
        }

        return $input;
    }

    /**
     * Check if a input-item exist
     *
     * @param string $index
     * @return bool
     */
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
        $output = $_POST;

        if ($this->request->getMethod() === 'post') {

            $contents = file_get_contents('php://input');

            if (strpos(trim($contents), '{') === 0) {
                $output = json_decode($contents, true);
                if ($output === false) {
                    $output = [];
                }
            }
        }

        return ($filter !== null) ? array_intersect_key($output, array_flip($filter)) : array_merge($_GET, $output);
    }

}
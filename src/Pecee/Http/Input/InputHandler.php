<?php

namespace Pecee\Http\Input;

use Pecee\Exceptions\InvalidArgumentException;
use Pecee\Http\Request;

class InputHandler
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

    /**
     * Input constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->parseInputs();
    }

    /**
     * Parse input values
     *
     */
    public function parseInputs(): void
    {
        /* Parse get requests */
        if (\count($_GET) !== 0) {
            $this->get = $this->handleGetPost($_GET);
        }

        /* Parse post requests */
        $postVars = $_POST;

        if (\in_array($this->request->getMethod(), ['put', 'patch', 'delete'], false) === true) {
            parse_str(file_get_contents('php://input'), $postVars);
        }

        if (\count($postVars) !== 0) {
            $this->post = $this->handleGetPost($postVars);
        }

        /* Parse get requests */
        if (\count($_FILES) !== 0) {
            $this->file = $this->parseFiles();
        }
    }

    /**
     * @return array
     */
    public function parseFiles(): array
    {
        $list = [];

        foreach ((array)$_FILES as $key => $value) {

            // Handle array input
            if (\is_array($value['name']) === false) {
                $values['index'] = $key;
                try {
                    $list[$key] = InputFile::createFromArray($values + $value);
                } catch (InvalidArgumentException $e) {

                }
                continue;
            }

            $keys = [$key];
            $files = $this->rearrangeFiles($value['name'], $keys, $value);

            if (isset($list[$key]) === true) {
                $list[$key][] = $files;
            } else {
                $list[$key] = $files;
            }

        }

        return $list;
    }

    protected function rearrangeFiles(array $values, &$index, $original): array
    {

        $originalIndex = $index[0];
        array_shift($index);

        $output = [];

        foreach ($values as $key => $value) {

            if (\is_array($original['name'][$key]) === false) {

                try {

                    $file = InputFile::createFromArray([
                        'index'    => (empty($key) === true && empty($originalIndex) === false) ? $originalIndex : $key,
                        'name'     => $original['name'][$key],
                        'error'    => $original['error'][$key],
                        'tmp_name' => $original['tmp_name'][$key],
                        'type'     => $original['type'][$key],
                        'size'     => $original['size'][$key],
                    ]);

                    if (isset($output[$key]) === true) {
                        $output[$key][] = $file;
                        continue;
                    }

                    $output[$key] = $file;
                    continue;

                } catch (InvalidArgumentException $e) {

                }
            }

            $index[] = $key;

            $files = $this->rearrangeFiles($value, $index, $original);

            if (isset($output[$key]) === true) {
                $output[$key][] = $files;
            } else {
                $output[$key] = $files;
            }

        }

        return $output;
    }

    protected function handleGetPost(array $array): array
    {
        $list = [];

        foreach ($array as $key => $value) {

            // Handle array input
            if (\is_array($value) === false) {
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
    public function findPost(string $index, ?string $defaultValue = null)
    {
        return $this->post[$index] ?? $defaultValue;
    }

    /**
     * Find file by index or return default value.
     *
     * @param string $index
     * @param string|null $defaultValue
     * @return InputFile|string
     */
    public function findFile(string $index, ?string $defaultValue = null)
    {
        return $this->file[$index] ?? $defaultValue;
    }

    /**
     * Find parameter/query-string by index or return default value.
     *
     * @param string $index
     * @param string|null $defaultValue
     * @return InputItem|string
     */
    public function findGet(string $index, ?string $defaultValue = null)
    {
        return $this->get[$index] ?? $defaultValue;
    }

    /**
     * Get input object
     *
     * @param string $index
     * @param array ...$methods
     * @return IInputItem|null
     */
    public function get(string $index, ...$methods): ?IInputItem
    {
        $element = null;

        if (\count($methods) === 0 || \in_array('get', $methods, true) === true) {
            $element = $this->findGet($index);
        }

        if (($element === null && \count($methods) === 0) || (\count($methods) !== 0 && \in_array('post', $methods, true) === true)) {
            $element = $this->findPost($index);
        }

        if (($element === null && \count($methods) === 0) || (\count($methods) !== 0 && \in_array('file', $methods, true) === true)) {
            $element = $this->findFile($index);
        }

        return $element;
    }

    /**
     * Get input element value matching index
     *
     * @param string $index
     * @param string|null $defaultValue
     * @param array ...$methods
     * @return string
     */
    public function getValue(string $index, ?string $defaultValue = null, ...$methods): ?string
    {
        $input = $this->get($index, ...$methods);
        return ($input === null || ($input !== null && trim($input->getValue()) === '')) ? $defaultValue : $input->getValue();
    }

    /**
     * Check if a input-item exist
     *
     * @param string $index
     * @param array ...$methods
     * @return bool
     */
    public function exists(string $index, ...$methods): bool
    {
        return $this->get($index, ...$methods) !== null;
    }

    /**
     * Get all get/post items
     * @param array|null $filter Only take items in filter
     * @return array
     */
    public function all(array $filter = null): array
    {
        $output = $_GET;

        if ($this->request->getMethod() === 'post') {

            // Append POST data
            $output += $_POST;

            $contents = file_get_contents('php://input');

            // Append any PHP-input json
            if (strpos(trim($contents), '{') === 0) {
                $post = json_decode($contents, true);
                if ($post !== false) {
                    $output += $post;
                }
            }
        }

        return ($filter !== null) ? array_intersect_key($output, array_flip($filter)) : $output;
    }

}
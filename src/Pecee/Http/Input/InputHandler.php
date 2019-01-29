<?php

namespace Pecee\Http\Input;

use Pecee\Http\Request;
use Pecee\Exceptions\InvalidArgumentException;

class InputHandler
{
    /**
     * @var array
     */
    protected $get = [];

    /**
     * @var array
     */
    protected $post = [];

    /**
     * @var array
     */
    protected $file = [];

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

    public function parseInputs(): void
    {
        if (\count($_GET) !== 0) {
            $this->get = $this->parseInputItem($_GET);
        }

        $postVars = $_POST;

        if (\in_array($this->request->getMethod(), ['put', 'patch', 'delete'], false) === true) {
            parse_str(file_get_contents('php://input'), $postVars);
        }

        if (\count($postVars) !== 0) {
            $this->post = $this->parseInputItem($postVars);
        }

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
            if (\is_array($value['name']) === false) {
                $values['index'] = $key;
                try {
                    $list[$key] = InputFile::createFromArray($values + $value);
                } catch (InvalidArgumentException $e) {
                    //
                }
                continue;
            }
            $keys = [$key];
            $files = $this->rearrangeFile($value['name'], $keys, $value);
            if (isset($list[$key]) === true) {
                $list[$key][] = $files;
            } else {
                $list[$key] = $files;
            }
        }

        return $list;
    }

    /**
     * @param array $values
     * @param array $index
     * @param array|null $original
     * @return array
     */
    protected function rearrangeFile(array $values, array &$index, ?array $original): array
    {
        $originalIndex = $index[0];
        array_shift($index);
        $output = [];
        foreach ($values as $key => $value) {
            if (\is_array($original['name'][$key]) === false) {
                try {
                    $file = InputFile::createFromArray([
                        'index' => (empty($key) === true && empty($originalIndex) === false) ? $originalIndex : $key,
                        'type' => $original['type'][$key],
                        'size' => $original['size'][$key],
                        'name' => $original['name'][$key],
                        'error' => $original['error'][$key],
                        'tmp_name' => $original['tmp_name'][$key],
                    ]);
                    if (isset($output[$key]) === true) {
                        $output[$key][] = $file;
                        continue;
                    }
                    $output[$key] = $file;
                    continue;
                } catch (InvalidArgumentException $e) {
                    //
                }
            }
            $index[] = $key;
            $files = $this->rearrangeFile($value, $index, $original);
            if (isset($output[$key]) === true) {
                $output[$key][] = $files;
            } else {
                $output[$key] = $files;
            }
        }

        return $output;
    }

    /**
     * @param array $array
     * @return array
     */
    protected function parseInputItem(array $array): array
    {
        $list = [];
        foreach ($array as $key => $value) {
            if (\is_array($value) === false) {
                $list[$key] = new InputItem($key, $value);
                continue;
            }
            $output = $this->parseInputItem($value);
            $list[$key] = $output;
        }

        return $list;
    }

    /**
     * @param string $index
     * @param mixed ...$methods
     * @return array|null|InputFile|InputItem|string
     */
    public function find(string $index, ...$methods)
    {
        $element = null;

        if (\count($methods) === 0 || \in_array('get', $methods, true) === true) {
            $element = $this->get($index);
        }

        if (($element === null && \count($methods) === 0) || (\count($methods) !== 0 && \in_array('post', $methods, true) === true)) {
            $element = $this->post($index);
        }

        if (($element === null && \count($methods) === 0) || (\count($methods) !== 0 && \in_array('file', $methods, true) === true)) {
            $element = $this->file($index);
        }

        return $element;
    }

    /**
     * @param string $index
     * @param null|string $defaultValue
     * @param array ...$methods
     * @return array|null|string
     */
    public function value(string $index, ?string $defaultValue = null, array ...$methods)
    {
        $input = $this->find($index, ...$methods);

        $output = [];

        if (\is_array($input) === true) {
            /* @var $item InputItem */
            foreach ($input as $item) {
                $output[] = $item->getValue();
            }

            return (\count($output) === 0) ? $defaultValue : $output;
        }

        return ($input === null || ($input !== null && trim($input->getValue()) === '')) ? $defaultValue : $input->getValue();
    }

    /**
     * @param string $index
     * @param array ...$methods
     * @return bool
     */
    public function exists(string $index, array ...$methods): bool
    {
        return $this->value($index, null, ...$methods) !== null;
    }

    /**
     * @param string $index
     * @param null|string $defaultValue
     * @return mixed|null|string
     */
    public function post(string $index, ?string $defaultValue = null)
    {
        return $this->post[$index] ?? $defaultValue;
    }

    /**
     * @param string $index
     * @param null|string $defaultValue
     * @return mixed|null|string
     */
    public function file(string $index, ?string $defaultValue = null)
    {
        return $this->file[$index] ?? $defaultValue;
    }

    /**
     * @param string $index
     * @param null|string $defaultValue
     * @return mixed|null|string
     */
    public function get(string $index, ?string $defaultValue = null)
    {
        return $this->get[$index] ?? $defaultValue;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function all(array $filter = []): array
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

        return (\count($filter) > 0) ? array_intersect_key($output, array_flip($filter)) : $output;
    }

    /**
     * @param string $key
     * @param InputItem $item
     */
    public function addGet(string $key, InputItem $item): void
    {
        $this->get[$key] = $item;
    }

    /**
     * @param string $key
     * @param InputItem $item
     */
    public function addPost(string $key, InputItem $item): void
    {
        $this->post[$key] = $item;
    }

    /**
     * @param string $key
     * @param InputFile $item
     */
    public function addFile(string $key, InputFile $item): void
    {
        $this->file[$key] = $item;
    }

}
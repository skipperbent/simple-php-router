<?php

namespace Pecee\Http\Input;

use Pecee\Exceptions\InvalidArgumentException;
use Pecee\Http\Request;

class InputHandler
{
    /**
     * @var array
     */
    protected $get = [];

    /**
     * Original get/params variables
     * @var array
     */
    protected $originalParams = [];

    /**
     * @var array
     */
    protected $post = [];

    /**
     * Original post variables
     * @var array
     */
    protected $originalPost = [];

    /**
     * @var array
     */
    protected $body = [];

    /**
     * Original body variables
     * @var array
     */
    protected $originalBody = [];

    /**
     * @var string
     */
    protected $body_plain = '';

    /**
     * @var array
     */
    protected $file = [];

    /**
     * Get original file variables
     * @var array
     */
    protected $originalFile = [];

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
        $this->originalParams = [];
        $this->get = [];
        $this->originalPost = [];
        $this->post = [];
        $this->body_plain = '';
        $this->originalBody = [];
        $this->body = [];
        $this->originalFile = [];
        $this->file = [];

        /* Parse get requests */
        if (\count($_GET) !== 0) {
            $this->originalParams = $_GET;
            $this->get = $this->parseInputItem($this->originalParams);
        }

        /* Parse post requests */
        if (\count($_POST) !== 0) {
            $this->originalPost = $_POST;
            $this->post = $this->parseInputItem($this->originalPost);
        }

        /* Get body */
        $this->body_plain = file_get_contents('php://input');

        /* Parse body */
        if (in_array($this->request->getMethod(), ['put', 'patch', 'delete', 'post'], false)) {
            if(strpos($this->request->getContentType(), 'application/json') !== false){
                $body = json_decode($this->body_plain, true);
                if ($body !== false) {
                    $this->originalBody = $body;
                    $this->body = $this->parseInputItem($this->originalBody);
                }
            }else if(strpos($this->request->getContentType(), 'application/x-www-form-urlencoded') !== false){
                parse_str(file_get_contents('php://input'), $body);
                $this->originalBody = $body;
                $this->body = $this->parseInputItem($this->originalBody);
            }
        }

        /* Parse get requests */
        if (\count($_FILES) !== 0) {
            $this->originalFile = $_FILES;
            $this->file = $this->parseFiles();
        }
    }

    /**
     * @return array
     */
    public function parseFiles(): array
    {
        $list = [];

        foreach ($_FILES as $key => $value) {

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
     * Rearrange multi-dimensional file object created by PHP.
     *
     * @param array $values
     * @param array $index
     * @param array|null $original
     * @return array
     */
    protected function rearrangeFile(array $values, &$index, $original): array
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
     * Parse input item from array
     *
     * @param array $array
     * @return array
     */
    protected function parseInputItem(array $array): array
    {
        $list = [];

        foreach ($array as $key => $value) {

            // Handle array input
            if (\is_array($value) === false) {
                $list[$key] = new InputItem($key, $value);
                continue;
            }

            $list[$key] = $this->parseInputItem($value);
        }

        return $list;
    }

    /**
     * Find input object
     *
     * @param string $index
     * @param string ...$methods
     * @return mixed
     */
    public function find(string $index, ...$methods)
    {
        return $this->toValue($this->findItem($index, ...$methods));
    }

    /**
     * @param string $index
     * @param string ...$methods
     * @return InputItem|null|array
     */
    public function findItem(string $index, ...$methods){
        $element = null;

        if (\count($methods) === 0 || \in_array(Request::REQUEST_TYPE_GET, $methods, true) === true) {
            $element = $this->get($index, null, true);
        }

        if (($element === null && \count($methods) === 0) || (\count($methods) !== 0 && \in_array(Request::REQUEST_TYPE_POST, $methods, true) === true)) {
            $element = $this->post($index, null, true);
        }

        if (($element === null && count($methods) === 0) || (count($methods) !== 0 && in_array('body', $methods, true))) {
            $element = $this->body($index, null, true);
        }

        if (($element === null && \count($methods) === 0) || (\count($methods) !== 0 && \in_array('file', $methods, true) === true)) {
            $element = $this->file($index, null, true);
        }

        return $element;
    }

    /**
     * @param InputItem|array|null $value
     * @return mixed
     */
    private function toValue($value)
    {
        if($value === null)
            return null;
        if(is_array($value)){
            $data = array();
            foreach ($value as $key => $subitem) {
                if($subitem === null)
                    continue;
                $data[$key] = $this->toValue($subitem);
            }
            return $data;
        }else{
            return $value->getValue();
        }
    }

    /**
     * Get input element value matching index
     *
     * @param string $index
     * @param string|object|null $defaultValue
     * @param string ...$methods
     * @return string|array
     */
    public function value(string $index, $defaultValue = null, ...$methods)
    {
        $input = $this->find($index, ...$methods);

        if($input === null)
            return $defaultValue;

        return $input ?? $defaultValue;
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
        return $this->value($index, null, ...$methods) !== null;
    }

    /**
     * Find post-value by index or return default value.
     *
     * @param string $index
     * @param mixed $defaultValue
     * @param bool $inputItem
     * @return mixed
     */
    public function post(string $index, $defaultValue = null, bool $inputItem = false)
    {
        if(!isset($this->post[$index]))
            return $defaultValue;
        $item = $this->post[$index];
        return ($inputItem ? $item : $this->toValue($item)) ?? $defaultValue;
    }

    /**
     * Find body-value by index or return default value.
     *
     * @param string $index
     * @param mixed $defaultValue
     * @param bool $inputItem
     * @return mixed
     */
    public function body(string $index, $defaultValue = null, bool $inputItem = false)
    {
        if(!isset($this->body[$index]))
            return $defaultValue;
        $item = $this->body[$index];
        return ($inputItem ? $item : $this->toValue($item)) ?? $defaultValue;
    }

    /**
     * Find file by index or return default value.
     *
     * @param string $index
     * @param mixed $defaultValue
     * @param bool $inputItem
     * @return mixed
     */
    public function file(string $index, $defaultValue = null, bool $inputItem = false)
    {
        if(!isset($this->file[$index]))
            return $defaultValue;
        $item = $this->file[$index];
        return ($inputItem ? $item : $this->toValue($item)) ?? $defaultValue;
    }

    /**
     * Find parameter/query-string by index or return default value.
     *
     * @param string $index
     * @param mixed $defaultValue
     * @param bool $inputItem
     * @return mixed
     */
    public function get(string $index, $defaultValue = null, bool $inputItem = false)
    {
        if(!isset($this->get[$index]))
            return $defaultValue;
        $item = $this->get[$index];
        return ($inputItem ? $item : $this->toValue($item)) ?? $defaultValue;
    }

    /**
     * @return string
     */
    public function getBodyPlain(): string{
        return $this->body_plain;
    }

    /**
     * Get all get/post items
     * @param array $filter Only take items in filter
     * @param bool $inputItem
     * @return array
     */
    public function all(array $filter = [], bool $inputItem = false): array
    {
        $output = array_merge($this->get, $this->post, $this->body);

        if(!$inputItem)
            $output = $this->toValue($output);

        $output = (\count($filter) > 0) ? array_intersect_key($output, array_flip($filter)) : $output;

        foreach ($filter as $filterKey) {
            if (array_key_exists($filterKey, $output) === false) {
                $output[$filterKey] = null;
            }
        }

        return $output;
    }

    /**
     * Add GET parameter
     *
     * @param string $key
     * @param InputItem $item
     */
    public function addGet(string $key, InputItem $item): void
    {
        $this->get[$key] = $item;
    }

    /**
     * Add POST parameter
     *
     * @param string $key
     * @param InputItem $item
     */
    public function addPost(string $key, InputItem $item): void
    {
        $this->post[$key] = $item;
    }

    /**
     * Add Body parameter
     *
     * @param string $key
     * @param InputItem $item
     */
    public function addBody(string $key, InputItem $item): void
    {
        $this->body[$key] = $item;
    }

    /**
     * Add FILE parameter
     *
     * @param string $key
     * @param InputFile $item
     */
    public function addFile(string $key, InputFile $item): void
    {
        $this->file[$key] = $item;
    }

    /**
     * Get original post variables
     * @return array
     */
    public function getOriginalPost(): array
    {
        return $this->originalPost;
    }

    /**
     * Set original post variables
     * @param array $post
     * @return static $this
     */
    public function setOriginalPost(array $post): self
    {
        $this->originalPost = $post;
        return $this;
    }

    /**
     * Get original get variables
     * @return array
     */
    public function getOriginalParams(): array
    {
        return $this->originalParams;
    }

    /**
     * Set original get-variables
     * @param array $params
     * @return static $this
     */
    public function setOriginalParams(array $params): self
    {
        $this->originalParams = $params;
        return $this;
    }

    /**
     * Get original file variables
     * @return array
     */
    public function getOriginalFile(): array
    {
        return $this->originalFile;
    }

    /**
     * Set original file posts variables
     * @param array $file
     * @return static $this
     */
    public function setOriginalFile(array $file): self
    {
        $this->originalFile = $file;
        return $this;
    }

}
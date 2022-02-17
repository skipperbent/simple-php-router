<?php

namespace Pecee\Http\Input;

use Closure;
use Pecee\Http\Input\Exceptions\InputsNotValidatedException;
use Pecee\Http\Input\Exceptions\InputValidationException;
use Pecee\Http\Request;

class InputValidator
{

    /* Static config settings */

    /**
     * Allow throwing exceptions
     * @var bool
     */
    private static $throwExceptions = true;

    /**
     * @param bool $throwExceptions
     */
    public static function setThrowExceptions(bool $throwExceptions): void
    {
        self::$throwExceptions = $throwExceptions;
    }

    /**
     * @return bool
     */
    public static function isThrowExceptions(): bool
    {
        return self::$throwExceptions;
    }

    /**
     * @var string|null $customValidatorRuleNamespace
     */
    private static $customValidatorRuleNamespace = null;

    /**
     * @param string $customValidatorRuleNamespace
     */
    public static function setCustomValidatorRuleNamespace(string $customValidatorRuleNamespace): void
    {
        self::$customValidatorRuleNamespace = $customValidatorRuleNamespace;
    }

    /**
     * @return string|null
     */
    public static function getCustomValidatorRuleNamespace(): ?string
    {
        return self::$customValidatorRuleNamespace;
    }

    /* InputValidator Object */

    /**
     * @var string|Closure|null
     */
    protected $rewriteCallbackOnFailure = null;
    /**
     * @var InputValidatorItem[]
     */
    protected $items = array();
    /**
     * @var bool|null
     */
    protected $valid = null;
    /**
     * @var InputValidatorItem[]|null
     */
    protected $errors = null;

    /**
     * Creates a new InputValidator
     * @return InputValidator
     */
    public static function make(): InputValidator
    {
        return new InputValidator();
    }

    public function __construct()
    {
    }

    /**
     * @param $settings
     * @return self
     */
    public function parseSettings($settings): self
    {
        if (is_array($settings)) {
            foreach ($settings as $key => $item) {
                if ($item instanceof InputValidatorItem)
                    $this->add($item);
                else if ((is_string($item) || is_array($item) || $item instanceof InputValidatorRule) && is_string($key)) {
                    $itemObject = InputValidatorItem::make($key);
                    $itemObject->parseSettings($item);
                    $this->add($itemObject);
                }
            }
        }
        return $this;
    }

    /**
     * @param string|Closure $callback
     * @return self
     */
    protected function rewriteCallbackOnFailure(string $callback): self
    {
        $this->rewriteCallbackOnFailure = $callback;
        return $this;
    }

    /**
     * @return InputValidatorItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param InputValidatorItem $validator
     * @return self
     */
    public function add(InputValidatorItem $validator): self
    {
        $this->items[] = $validator;
        return $this;
    }

    /**
     * Set all Input Items that should be validated
     * @param InputValidatorItem[] $items
     * @return self
     */
    public function items(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    /**
     * Validate all Items
     * @param Request $request
     * @return bool
     */
    public function validate(Request $request): bool
    {
        $this->errors = array();
        $inputHandler = $request->getInputHandler();
        foreach ($this->getItems() as $item) {
            $inputItem = $inputHandler->find($item->getKey());
            if (!$inputItem instanceof IInputItem) {
                $inputItem = new InputItem($item->getKey(), $inputItem);
            }
            $callback = $item->validate($inputItem);
            if (!$callback)
                $this->errors[] = $item;
        }
        $this->valid = empty($this->errors);
        if ($this->fails()) {
            if ($this->rewriteCallbackOnFailure !== null)
                $request->setRewriteCallback($this->rewriteCallbackOnFailure);
            if(self::isThrowExceptions()){
                throw new InputValidationException('Failed to validate inputs', $this->getErrors());
            }
        }
        return $this->valid;
    }

    /**
     * Check if inputs passed validation
     * @return bool
     */
    public function passes(): bool
    {
        if ($this->valid === null)
            throw new InputsNotValidatedException();
        return $this->valid;
    }

    /**
     * Check if inputs failed valida
     * @return bool
     */
    public function fails(): bool
    {
        if ($this->valid === null)
            throw new InputsNotValidatedException();
        return !$this->valid;
    }

    /**
     * @return InputValidatorItem[]|null
     */
    public function getErrors(): ?array
    {
        if ($this->valid === null)
            throw new InputsNotValidatedException();
        return $this->errors;
    }
}
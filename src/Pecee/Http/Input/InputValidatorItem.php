<?php

namespace Pecee\Http\Input;

use Pecee\Http\Input\Exceptions\InputsNotValidatedException;
use Pecee\Http\Input\ValidatorRules\ValidatorRuleMax;
use Pecee\Http\Input\ValidatorRules\ValidatorRuleNullable;
use Pecee\Http\Input\ValidatorRules\ValidatorRuleString;

/**
 * Class InputValidatorItem
 * @package Pecee\Http\Input
 *
 * @method InputValidatorItem boolean()
 */
class InputValidatorItem
{

    /**
     * Key of the Input value
     * Syntax: key or parentkey.childkey
     * @var string
     */
    protected $key;
    /**
     * @var InputValidatorRule[]
     */
    protected $rules = array();
    /**
     * @var bool|null
     */
    protected $valid = null;
    /**
     * @var InputValidatorRule[]|null
     */
    protected $errors = null;

    /**
     * @var IInputItem|null $inputItem - Set after validation
     */
    protected $inputItem = null;

    /**
     * @param string $key
     * @return InputValidatorItem
     */
    public static function make(string $key): InputValidatorItem
    {
        return new InputValidatorItem($key);
    }

    /**
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function parseSettings(string $settings)
    {
        $matches = array();
        //Add "\\\\" to allow one Backslash
        //https://stackoverflow.com/questions/11044136/right-way-to-escape-backslash-in-php-regex/15369828#answer-15369828
        preg_match_all('/([a-zA-Z\\\\=\/<>]+)(?::((?:\\\\[:|]|[^:\|])+))?\|?/', $settings, $matches);
        for ($i = 0; $i < sizeof($matches[0]); $i++) {
            $tag = $matches[1][$i];
            $attributes = array_filter(explode(',', $matches[2][$i]), function ($attribute) {
                return !empty($attribute);
            });

            $this->addRuleByTag($tag, $attributes);
        }
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return array
     */
    private function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @param string $rule - Rule Tag or Classname
     * @param array $attributes
     * @return self
     */
    private function addRuleByTag(string $rule, array $attributes = array()): self
    {
        $class = null;
        $rule = str_replace('_', '', ucwords($rule, '_'));

        if (strpos($rule, '\\') !== false && class_exists($rule)) {
            $class = $rule;
        } else if (class_exists('Pecee\Http\Input\ValidatorRules\ValidatorRule' . ucfirst(strtolower($rule)))) {
            $class = 'Pecee\Http\Input\ValidatorRules\ValidatorRule' . ucfirst(strtolower($rule));
        } else if (InputValidator::getCustomValidatorRuleNamespace() !== null && class_exists(InputValidator::getCustomValidatorRuleNamespace() . '\ValidatorRule' . ucfirst(strtolower($rule)))) {
            $class = InputValidator::getCustomValidatorRuleNamespace() . '\ValidatorRule' . ucfirst(strtolower($rule));
        }
        if ($class !== null && is_a($class, IInputValidatorRule::class, true)) {
            $this->rules[] = $class::make(...$attributes);
        }
        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this|InputValidatorItem
     */
    public function __call($name, $arguments)
    {
        return $this->addRuleByTag($name, $arguments);
    }

    /**
     * @param IInputItem $inputItem
     * @return bool
     */
    public function validate(IInputItem $inputItem): bool
    {
        $this->inputItem = $inputItem;
        $this->errors = array();

        $nullable = array_filter($this->getRules(), function ($rule) {
            return $rule instanceof ValidatorRuleNullable;
        });
        // If the nullable rule is present and the value is null we move on without validation
        if (empty($nullable) || $inputItem->getValue() !== null) {
            foreach ($this->getRules() as $rule) {
                $callback = $rule->validate($inputItem);
                if (!$callback)
                    $this->errors[] = $rule;
            }
        }
        $this->valid = empty($this->errors);
        return $this->valid;
    }

    /**
     * @return IInputItem
     */
    private function getInputItem(): IInputItem
    {
        if ($this->valid === null)
            throw new InputsNotValidatedException();
        return $this->inputItem;
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
     * @return InputValidatorRule[]|null
     */
    public function getErrors(): ?array
    {
        if ($this->valid === null)
            throw new InputsNotValidatedException();
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getErrorMessages(): array
    {
        if ($this->valid === null)
            throw new InputsNotValidatedException();
        $messages = array();
        foreach ($this->getErrors() as $rule) {
            $messages[] = $rule->formatErrorMessage($this->getInputItem()->getIndex());
        }
        return $messages;
    }

}

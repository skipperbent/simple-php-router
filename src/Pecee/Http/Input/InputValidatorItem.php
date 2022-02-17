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
 * @method InputValidatorItem array()
 * @method InputValidatorItem boolean()
 * @method InputValidatorItem contains($value)
 * @method InputValidatorItem custom($callable)
 * @method InputValidatorItem email()
 * @method InputValidatorItem endsWith($value)
 * @method InputValidatorItem equals($value)
 * @method InputValidatorItem file()
 * @method InputValidatorItem float()
 * @method InputValidatorItem in($array)
 * @method InputValidatorItem integer()
 * @method InputValidatorItem ip()
 * @method InputValidatorItem matches($regEx)
 * @method InputValidatorItem max($value)
 *
 * @method InputValidatorItem min($value)
 * @method InputValidatorItem notNull()
 * @method InputValidatorItem nullable()
 * @method InputValidatorItem numeric()
 * @method InputValidatorItem required()
 * @method InputValidatorItem size()
 * @method InputValidatorItem startsWith($value)
 * @method InputValidatorItem string()
 * @method InputValidatorItem xss()
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

    /**
     * @param string|array|InputValidatorRule $settings
     * @return void
     */
    public function parseSettings($settings)
    {
        if(is_string($settings)){
            $matches = array();
            //Add "\\\\" to allow one Backslash
            //https://stackoverflow.com/questions/11044136/right-way-to-escape-backslash-in-php-regex/15369828#answer-15369828
            preg_match_all('/([a-zA-Z\\\\=\/<>]+)(?::((?:\\\\[:|]|[^:\|])+))?\|?/', $settings, $matches);
            for($i = 0; $i < sizeof($matches[0]); $i++){
                $tag = $matches[1][$i];
                $attributes = array_filter(explode(',', $matches[2][$i]), function ($attribute){
                    return !empty($attribute);
                });

                $this->addRuleByTag($tag, $attributes);
            }
        }else if(is_array($settings)){
            foreach($settings as $setting){
                if($setting instanceof InputValidatorRule){
                    $this->addRule($setting);
                }
            }
        }else if($settings instanceof InputValidatorRule){
            $this->addRule($settings);
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
     * @param InputValidatorRule $rule
     * @return self
     */
    private function addRule(InputValidatorRule $rule): self
    {
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * @param string $rule - Rule Tag or Classname
     * @param array $attributes
     * @return self
     */
    private function addRuleByTag(string $rule, array $attributes = array()): self
    {
        $rule_object = $this->parseRuleByTag($rule, $attributes);

        if ($rule_object !== null) {
            $this->rules[] = $rule_object;
        }
        return $this;
    }

    private function parseRuleByTag(string $rule, array $attributes = array())
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
            return $class::make(...$attributes);
        }
        return null;
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
            /**
             * Key: rule tag
             * Value: 0 = valid; 1 = error; 2 = thrown
             *
             * @var array<string, int> $rules_cache
             */
            $rules_cache = array();
            foreach ($this->getRules() as $rule) {
                if(isset($rules_cache[$rule->getTag()])){
                    if($rules_cache[$rule->getTag()] === 1){
                        $this->errors[] = $rule;
                        $rules_cache[$rule->getTag()] = 2;
                    }
                    continue;
                }
                $require_valid = empty($rule->getRequiredRules());
                foreach($rule->getRequiredRules() as $required_rule){
                    if(isset($rules_cache[$required_rule])){
                        if($rules_cache[$required_rule] === 0){
                            $require_valid = true;
                            break;
                        }else if($rules_cache[$required_rule] > 0){
                            continue;
                        }
                    }
                    $required_rule_obj = $this->parseRuleByTag($required_rule);
                    if($required_rule_obj !== null){
                        if($required_rule_obj->validate($inputItem)){
                            $rules_cache[$rule->getTag()] = 0;
                            $require_valid = true;
                            break;
                        } else {
                            $rules_cache[$rule->getTag()] = 1;
                        }
                    }
                }
                if($require_valid){
                    $callback = $rule->validate($inputItem);
                    if(!$callback){
                        $this->errors[] = $rule;
                        $rules_cache[$rule->getTag()] = 2;
                    }else{
                        $rules_cache[$rule->getTag()] = 0;
                    }
                }else{
                    $this->errors[] = $rule;
                    $rules_cache[$rule->getTag()] = 2;
                }
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

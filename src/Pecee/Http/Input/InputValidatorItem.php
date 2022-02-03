<?php

namespace Pecee\Http\Input;

use Pecee\Http\Input\Exceptions\InputsNotValidatedException;
use Pecee\Http\Input\ValidatorRules\ValidatorRuleMax;
use Pecee\Http\Input\ValidatorRules\ValidatorRuleString;

class InputValidatorItem{

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
     * @param string $key
     * @return InputValidatorItem
     */
    public static function make(string $key): InputValidatorItem{
        return new InputValidatorItem($key);
    }

    /**
     * @param string $key
     */
    public function __construct(string $key){
        $this->key = $key;
    }

    public function parseSettings(string $settings){
        $matches = array();
        preg_match_all('/([a-zA-Z]+)(?::([a-z-A-Z0-9]+))*/', $settings, $matches);
        for($i = 0; $i < sizeof($matches[0]); $i++){
            $tag = $matches[1][$i];
            $attributes = array();
            for($j = 2; $j < sizeof($matches); $j++){
                if($matches[$j][$i] !== '')
                    $attributes[] = $matches[$j][$i];
            }
            $this->addRuleByTag($tag, $attributes);
        }
    }

    /**
     * @return string
     */
    public function getKey(): string{
        return $this->key;
    }

    /**
     * @return array
     */
    private function getRules(): array{
        return $this->rules;
    }

    /**
     * @param string $rule - Rule Tag or Classname
     * @param array $attributes
     * @return self
     */
    private function addRuleByTag(string $rule, array $attributes = array()): self{
        $class = null;
        if(strpos($rule, '\\') !== false && class_exists($rule)){
            $class = $rule;
        }else if(class_exists('Pecee\Http\Input\ValidatorRules\ValidatorRule' . ucfirst(strtolower($rule)))){
            $class = 'Pecee\Http\Input\ValidatorRules\ValidatorRule' . ucfirst(strtolower($rule));
        }else if(InputValidator::getCustomValidatorRuleNamespace() !== null && class_exists(InputValidator::getCustomValidatorRuleNamespace() . '\ValidatorRule' . ucfirst(strtolower($rule)))){
            $class = InputValidator::getCustomValidatorRuleNamespace() . '\ValidatorRule' . ucfirst(strtolower($rule));
        }
        if($class !== null && is_a($class, IInputValidatorRule::class, true)){
            $this->rules[] = $class::make(...$attributes);
        }
        return $this;
    }

    /**
     * @return self
     */
    public function isString(): self{
        $this->rules[] = ValidatorRuleString::make();
        return $this;
    }

    /**
     * @param int $max
     * @return self
     */
    public function max(int $max): self{
        $this->rules[] = ValidatorRuleMax::make($max);
        return $this;
    }

    public function validate(IInputItem $inputItem): bool{
        $this->errors = array();
        foreach($this->getRules() as $rule){
            $callback = $rule->validate($inputItem);
            if(!$callback)
                $this->errors[] = $rule;
        }
        $this->valid = empty($this->errors);
        return $this->valid;
    }

    /**
     * Check if inputs passed validation
     * @return bool
     */
    public function passes(): bool{
        if($this->valid === null)
            throw new InputsNotValidatedException();
        return $this->valid;
    }

    /**
     * Check if inputs failed valida
     * @return bool
     */
    public function fails(): bool{
        if($this->valid === null)
            throw new InputsNotValidatedException();
        return !$this->valid;
    }

    /**
     * @return InputValidatorRule[]|null
     */
    public function getErrors(): ?array{
        if($this->valid === null)
            throw new InputsNotValidatedException();
        return $this->errors;
    }

}
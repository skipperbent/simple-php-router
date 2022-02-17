<?php

namespace Pecee\Http\Input\Exceptions;

use Exception;
use Pecee\Http\Input\InputValidatorItem;
use Pecee\Http\Input\InputValidatorRule;
use Throwable;

class InputValidationException extends Exception
{

    /**
     * @var InputValidatorItem[] $errors
     */
    private $errors;

    public function __construct($message, $errors = array(), $code = 0, Throwable $previous = null) {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return InputValidatorItem[]
     */
    public function getErrors(): array{
        return $this->errors;
    }

    /**
     * @return InputValidatorRule[]|null
     */
    public function getErrorsForItem(string $key): ?array{
        foreach($this->getErrors() as $item){
            if($item->getKey() == $key)
                return $item->getErrors();
        }
        return null;
    }

    /**
     * @return array[]
     */
    public function getErrorMessages(): array{
        $messages = array();
        foreach($this->getErrors() as $item){
            $messages[] = $item->getErrorMessages();
        }
        return $messages;
    }

    /**
     * @return string
     */
    public function getDetailedMessage(): string{
        $messages = array();
        foreach($this->getErrors() as $item){
            $messages[] = join(', ', $item->getErrorMessages());
        }
        return 'Failed to validate inputs: ' . join('; ', $messages);
    }

}
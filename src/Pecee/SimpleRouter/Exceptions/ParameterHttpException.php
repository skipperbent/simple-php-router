<?php

namespace Pecee\SimpleRouter\Exceptions;

use Throwable;

class ParameterHttpException extends NotFoundHttpException
{
    protected $class;
    protected $method;
    protected $requiredParameters;
    protected $unusedParameters;

    public function __construct(?string $class = null, ?string $method = null, array $requiredParameters = array(), array $unusedParameters = array(), $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->class = $class;
        $this->method = $method;
        $this->requiredParameters = $requiredParameters;
        $this->unusedParameters = $unusedParameters;
    }

    /**
     * Get class name
     * @return string|null
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * Get method
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getRequiredParameters(): array
    {
        return $this->requiredParameters;
    }

    /**
     * @return array
     */
    public function getUnusedParameters(): array
    {
        return $this->unusedParameters;
    }

}
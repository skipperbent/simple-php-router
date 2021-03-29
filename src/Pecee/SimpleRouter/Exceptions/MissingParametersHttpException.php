<?php

namespace Pecee\SimpleRouter\Exceptions;

use Throwable;

class MissingParametersHttpException extends NotFoundHttpException
{
    protected $class;
    protected $method;
    protected $missingParameters;

    public function __construct(?string $class = null, ?string $method = null, array $missingParameters = array(), $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->class = $class;
        $this->method = $method;
        $this->missingParameters = $missingParameters;
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
    public function getMissingParameters(): array
    {
        return $this->missingParameters;
    }

}
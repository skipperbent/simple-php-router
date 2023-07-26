<?php declare(strict_types=1);

namespace Pecee\SimpleRouter\Exceptions;

use Throwable;

class ClassNotFoundHttpException extends NotFoundHttpException
{
    /**
     * @var string
     */
    protected string $class;

    /**
     * @var string|null
     */
    protected ?string $method = null;

    public function __construct(string $class, ?string $method = null, string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Get class name
     * @return string
     */
    public function getClass(): string
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

}
<?php
// Path: core/Exceptions/CoreException.php

namespace Core\Exceptions;

use Exception;

/**
 * Base abstract exception for all custom ERP exceptions.
 */
abstract class CoreException extends Exception
{
    protected int $statusCode = 500;
    protected array $context = [];

    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
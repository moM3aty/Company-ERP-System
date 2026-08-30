<?php
// Path: core/Exceptions/ValidationException.php

namespace Core\Exceptions;

use Exception;

class ValidationException extends Exception
{
    protected array $errors;
    protected int $statusCode = 422;

    public function __construct(array $errors, string $message = "Validation Failed")
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
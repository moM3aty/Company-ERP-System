<?php
// Path: core/Exceptions/BusinessException.php

namespace Core\Exceptions;

/**
 * Exception thrown when a Domain/Business rule is violated (e.g., Insufficient Stock).
 */
class BusinessException extends CoreException
{
    protected int $statusCode = 422;

    public function __construct(string $message, int $code = 422, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
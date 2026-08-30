<?php
// Path: core/Exceptions/ConflictException.php

namespace Core\Exceptions;

/**
 * Thrown when trying to create a duplicate record or update a locked entity.
 */
class ConflictException extends CoreException
{
    protected int $statusCode = 409;

    public function __construct(string $message = "Resource conflict occurred.", int $code = 409, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
<?php
// Path: core/Exceptions/DatabaseException.php

namespace Core\Exceptions;

class DatabaseException extends CoreException
{
    protected int $statusCode = 500;

    public function __construct(string $message = "Database operation failed.", int $code = 500, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
<?php
// Path: core/Exceptions/StorageException.php

namespace Core\Exceptions;

class StorageException extends CoreException
{
    protected int $statusCode = 500;

    public function __construct(string $message = "File storage operation failed.", int $code = 500, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
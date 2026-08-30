<?php
// Path: core/Exceptions/NotFoundException.php

namespace Core\Exceptions;

use Exception;

class NotFoundException extends Exception
{
    protected int $statusCode = 404;

    public function __construct(string $message = "Resource Not Found")
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
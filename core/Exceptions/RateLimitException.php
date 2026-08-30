<?php
// Path: core/Exceptions/RateLimitException.php

namespace Core\Exceptions;

class RateLimitException extends CoreException
{
    protected int $statusCode = 429; // Too Many Requests

    public function __construct(string $message = "Too Many Requests.", int $code = 429, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
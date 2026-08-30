<?php
// Path: core/Exceptions/AuthenticationException.php

namespace Core\Exceptions;

class AuthenticationException extends CoreException
{
    protected int $statusCode = 401;

    public function __construct(string $message = "Unauthenticated.", int $code = 401, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
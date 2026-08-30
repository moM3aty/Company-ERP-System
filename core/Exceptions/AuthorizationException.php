<?php
// Path: core/Exceptions/AuthorizationException.php

namespace Core\Exceptions;

class AuthorizationException extends CoreException
{
    protected int $statusCode = 403;

    public function __construct(string $message = "This action is unauthorized.", int $code = 403, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
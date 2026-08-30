<?php
// Path: core/Exceptions/ConfigurationException.php

namespace Core\Exceptions;

class ConfigurationException extends CoreException
{
    protected int $statusCode = 500;

    public function __construct(string $message = "System configuration error.", int $code = 500, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
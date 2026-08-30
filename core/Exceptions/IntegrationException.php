<?php
// Path: core/Exceptions/IntegrationException.php

namespace Core\Exceptions;

/**
 * Thrown when an external API or third-party service fails.
 */
class IntegrationException extends CoreException
{
    protected int $statusCode = 502; // Bad Gateway

    public function __construct(string $message = "External integration failed.", int $code = 502, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
<?php

namespace CleaniqueCoders\RunningNumber\Exceptions;

use Exception;

/**
 * Exception thrown when configuration is invalid
 */
class ConfigurationException extends Exception
{
    /**
     * Create a new ConfigurationException instance
     *
     * @param  string  $message  The error message
     * @param  int  $code  The error code
     * @param  \Throwable|null  $previous  The previous exception
     */
    public function __construct(string $message = 'Invalid configuration', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

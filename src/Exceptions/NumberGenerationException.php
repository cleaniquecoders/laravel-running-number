<?php

namespace CleaniqueCoders\RunningNumber\Exceptions;

use Exception;

/**
 * Exception thrown when number generation fails
 */
class NumberGenerationException extends Exception
{
    /**
     * Create a new NumberGenerationException instance
     *
     * @param  string  $message  The error message
     * @param  int  $code  The error code
     * @param  \Throwable|null  $previous  The previous exception
     */
    public function __construct(string $message = 'Failed to generate running number', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

<?php

namespace CleaniqueCoders\RunningNumber\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid or unsupported running number type is used
 *
 * This occurs when attempting to generate a running number with a type
 * that is not configured in the running-number.types array.
 */
class InvalidRunningNumberTypeException extends Exception
{
    /**
     * Create a new InvalidRunningNumberTypeException instance
     *
     * @param  string  $message  The error message
     * @param  int  $code  The error code
     * @param  \Throwable|null  $previous  The previous exception
     */
    public function __construct(string $message = 'Invalid running number type', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

<?php

namespace CleaniqueCoders\RunningNumber\Exceptions;

use Exception;

/**
 * Exception thrown when the maximum number limit is reached
 *
 * This occurs when attempting to generate a number that would exceed
 * the configured maximum limit for a running number type.
 */
class MaxNumberReachedException extends Exception
{
    /**
     * Create a new MaxNumberReachedException instance
     *
     * @param  string  $type  The running number type
     * @param  int  $maxNumber  The maximum number limit
     */
    public function __construct(string $type, int $maxNumber)
    {
        parent::__construct(
            sprintf('Maximum number %d reached for running number type "%s"', $maxNumber, $type)
        );
    }
}

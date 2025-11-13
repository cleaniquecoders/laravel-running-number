<?php

namespace CleaniqueCoders\RunningNumber\Exceptions;

use Exception;

class MaxNumberReachedException extends Exception
{
    public function __construct(string $type, int $maxNumber)
    {
        parent::__construct("Maximum number {$maxNumber} reached for type {$type}");
    }
}

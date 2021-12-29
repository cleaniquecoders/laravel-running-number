<?php

namespace CleaniqueCoders\RunningNumber\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CleaniqueCoders\RunningNumber\RunningNumber
 */
class RunningNumber extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-running-number';
    }
}

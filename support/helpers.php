<?php

use CleaniqueCoders\RunningNumber\Generator;

if (! function_exists('running_number')) {
    function running_number()
    {
        return Generator::make();
    }
}

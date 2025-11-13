<?php

use CleaniqueCoders\RunningNumber\Http\Controllers\RunningNumberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Running Number API Routes
|--------------------------------------------------------------------------
|
| These routes provide REST API endpoints for managing running numbers.
| Enable in config: 'api.enabled' => true
| Configure prefix and middleware in config: 'api.prefix' and 'api.middleware'
|
*/

$prefix = config('running-number.api.prefix', 'api/running-numbers');
$middleware = config('running-number.api.middleware', ['api']);

Route::prefix($prefix)
    ->middleware($middleware)
    ->group(function () {
        // Generate a new running number
        Route::post('generate', [RunningNumberController::class, 'generate'])
            ->name('running-number.api.generate');

        // Get current running number information
        Route::get('current', [RunningNumberController::class, 'current'])
            ->name('running-number.api.current');

        // Preview next running number
        Route::get('preview', [RunningNumberController::class, 'preview'])
            ->name('running-number.api.preview');

        // List all running numbers
        Route::get('list', [RunningNumberController::class, 'list'])
            ->name('running-number.api.list');
    });

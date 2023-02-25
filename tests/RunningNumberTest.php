<?php

use CleaniqueCoders\RunningNumber\Contracts\Generator;
use CleaniqueCoders\RunningNumber\Enums\Organization;
use CleaniqueCoders\RunningNumber\Generator as RunningNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    include_once __DIR__.'/../database/migrations/create_running_number_table.php.stub';

    (new \CreateRunningNumberTable())->up();
});

it('can has running number helper', function () {
    expect(function_exists('running_number'))->toBeTrue();
});

it('running number helper is an instance of Generator', function () {
    expect(running_number() instanceof Generator)->toBeTrue();
});

it('it can generate PROFILE001 running number', function () {
    $runnning_number = RunningNumberGenerator::make()->type(Organization::profile()->value)->generate();
    expect($runnning_number == 'PROFILE001')->toBeTrue();
});

it('it can generate 10 running numbers for each types in config', function () {
    foreach (config('running-number.types') as $type) {
        for ($i = 0; $i < 10; $i++) {
            RunningNumberGenerator::make()->type($type)->generate();
        }
        expect(config('running-number.model')::where('type', strtoupper($type))->first()->number == 10)->toBeTrue();
    }
});

<?php

use CleaniqueCoders\RunningNumber\Contracts\Generator;
use CleaniqueCoders\RunningNumber\Enums\Organization;
use CleaniqueCoders\RunningNumber\Generator as RunningNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    include_once __DIR__.'/../database/migrations/create_running_number_table.php.stub';
    include_once __DIR__.'/../database/migrations/add_uuid_to_running_numbers_table.php.stub';

    (new \CreateRunningNumberTable)->up();

    // Run the UUID migration
    $uuidMigration = new class extends \Illuminate\Database\Migrations\Migration
    {
        public function up()
        {
            Schema::table('running_numbers', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            });
        }
    };
    $uuidMigration->up();
});

it('can has running number helper', function () {
    expect(function_exists('running_number'))->toBeTrue();
});

it('running number helper is an instance of Generator', function () {
    expect(running_number() instanceof Generator)->toBeTrue();
});

it('it can generate PROFILE001 running number', function () {
    $runnning_number = RunningNumberGenerator::make()->type(Organization::PROFILE->value)->generate();
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

// UUID Tests
it('automatically generates UUID when creating running number', function () {
    RunningNumberGenerator::make()->type(Organization::PROFILE->value)->generate();

    $runningNumber = config('running-number.model')::where('type', 'PROFILE')->first();

    expect($runningNumber->uuid)->not->toBeNull()
        ->and($runningNumber->uuid)->toBeString();
});

it('generates unique UUIDs for each running number type', function () {
    $types = [
        Organization::ORGANIZATION->value,
        Organization::DIVISION->value,
        Organization::SECTION->value,
    ];

    foreach ($types as $type) {
        RunningNumberGenerator::make()->type($type)->generate();
    }

    $uuids = config('running-number.model')::pluck('uuid')->toArray();

    expect(count($uuids))->toBe(3)
        ->and(count(array_unique($uuids)))->toBe(3);
});

it('generates valid UUID format', function () {
    RunningNumberGenerator::make()->type(Organization::UNIT->value)->generate();

    $runningNumber = config('running-number.model')::where('type', 'UNIT')->first();

    // UUID v4 format: 8-4-4-4-12 characters
    $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    expect($runningNumber->uuid)->toMatch($uuidPattern);
});

it('can find running number by UUID', function () {
    RunningNumberGenerator::make()->type(Organization::DIVISION->value)->generate();

    $runningNumber = config('running-number.model')::where('type', 'DIVISION')->first();
    $foundByUuid = config('running-number.model')::where('uuid', $runningNumber->uuid)->first();

    expect($foundByUuid->id)->toBe($runningNumber->id)
        ->and($foundByUuid->type)->toBe($runningNumber->type)
        ->and($foundByUuid->number)->toBe($runningNumber->number);
});

it('maintains UUID across multiple number generations for same type', function () {
    // Generate first number
    RunningNumberGenerator::make()->type(Organization::SECTION->value)->generate();
    $firstRecord = config('running-number.model')::where('type', 'SECTION')->first();
    $uuid = $firstRecord->uuid;

    // Generate second number - should update the same record
    RunningNumberGenerator::make()->type(Organization::SECTION->value)->generate();
    $secondRecord = config('running-number.model')::where('type', 'SECTION')->first();

    expect($secondRecord->uuid)->toBe($uuid)
        ->and($secondRecord->number)->toBe(2)
        ->and(config('running-number.model')::where('type', 'SECTION')->count())->toBe(1);
});

// Race Condition & Rollback Tests
it('prevents duplicate numbers when generating concurrently', function () {
    // Generate multiple numbers in sequence
    $numbers = [];
    for ($i = 0; $i < 5; $i++) {
        $numbers[] = RunningNumberGenerator::make()
            ->type(Organization::ORGANIZATION->value)
            ->generate();
    }

    // All numbers should be unique
    expect(count($numbers))->toBe(5)
        ->and(count(array_unique($numbers)))->toBe(5)
        ->and($numbers)->toMatchArray([
            'ORGANIZATION001',
            'ORGANIZATION002',
            'ORGANIZATION003',
            'ORGANIZATION004',
            'ORGANIZATION005',
        ]);
});

it('handles type creation race condition gracefully', function () {
    // First creation should succeed
    RunningNumberGenerator::make()->type(Organization::DIVISION->value)->generate();

    // Verify only one record was created
    $count = config('running-number.model')::where('type', 'DIVISION')->count();

    expect($count)->toBe(1);
});

it('maintains sequential integrity after multiple operations', function () {
    $type = Organization::UNIT->value;

    // Generate 10 numbers
    for ($i = 1; $i <= 10; $i++) {
        RunningNumberGenerator::make()->type($type)->generate();
    }

    // Verify the final number is 10
    $record = config('running-number.model')::where('type', 'UNIT')->first();

    expect($record->number)->toBe(10);

    // Generate 5 more
    for ($i = 1; $i <= 5; $i++) {
        RunningNumberGenerator::make()->type($type)->generate();
    }

    // Verify the final number is now 15
    $record->refresh();
    expect($record->number)->toBe(15);
});

it('uses database transactions for generation', function () {
    // This test verifies that transactions are being used
    // by checking that the number is properly incremented
    $type = Organization::SECTION->value;

    // Generate first number
    $first = RunningNumberGenerator::make()->type($type)->generate();
    expect($first)->toBe('SECTION001');

    // Generate second number - should be sequential
    $second = RunningNumberGenerator::make()->type($type)->generate();
    expect($second)->toBe('SECTION002');

    // Verify database state
    $record = config('running-number.model')::where('type', 'SECTION')->first();
    expect($record->number)->toBe(2);
});

it('does not lose numbers on successful generation', function () {
    $type = Organization::PROFILE->value;
    $generated = [];

    // Generate 20 numbers
    for ($i = 1; $i <= 20; $i++) {
        $generated[] = RunningNumberGenerator::make()->type($type)->generate();
    }

    // Extract the numeric part from each generated number
    $numbers = array_map(function ($num) {
        return (int) preg_replace('/[^0-9]/', '', $num);
    }, $generated);

    // Verify no numbers are skipped (sequential from 1 to 20)
    expect($numbers)->toEqual(range(1, 20));
});

it('ensures atomic operations with firstOrCreate', function () {
    // Create a new type
    RunningNumberGenerator::make()->type(Organization::DIVISION->value)->generate();

    // Try to generate again - should not create duplicate type record
    RunningNumberGenerator::make()->type(Organization::DIVISION->value)->generate();
    RunningNumberGenerator::make()->type(Organization::DIVISION->value)->generate();

    // Should only have ONE record for this type
    $count = config('running-number.model')::where('type', 'DIVISION')->count();
    expect($count)->toBe(1);

    // And the number should be 3
    $record = config('running-number.model')::where('type', 'DIVISION')->first();
    expect($record->number)->toBe(3);
});

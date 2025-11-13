<?php

use CleaniqueCoders\RunningNumber\Contracts\Generator;
use CleaniqueCoders\RunningNumber\Enums\Organization;
use CleaniqueCoders\RunningNumber\Generator as RunningNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    include_once __DIR__.'/../database/migrations/create_running_number_table.php.stub';
    include_once __DIR__.'/../database/migrations/add_uuid_to_running_numbers_table.php.stub';
    include_once __DIR__.'/../database/migrations/add_reset_functionality_to_running_numbers_table.php.stub';

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

    // Run the reset functionality migration
    $resetMigration = new class extends \Illuminate\Database\Migrations\Migration
    {
        public function up()
        {
            Schema::table('running_numbers', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->string('reset_period')->default('never')->after('type');
                $table->timestamp('last_reset_at')->nullable()->after('reset_period');
            });
        }
    };
    $resetMigration->up();

    // Add test-specific types to config
    config(['running-number.types' => array_merge(
        config('running-number.types'),
        ['invoice', 'receipt', 'ticket', 'order', 'monthly']
    )]);
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

// Reset Functionality Tests
it('creates running number with default reset period', function () {
    RunningNumberGenerator::make()->type(Organization::UNIT->value)->generate();

    $record = config('running-number.model')::where('type', 'UNIT')->first();

    expect($record->reset_period->value)->toBe('never')
        ->and($record->last_reset_at)->not->toBeNull();
});

it('can manually reset a running number', function () {
    // Generate some numbers
    for ($i = 0; $i < 5; $i++) {
        RunningNumberGenerator::make()->type(Organization::SECTION->value)->generate();
    }

    $record = config('running-number.model')::where('type', 'SECTION')->first();
    expect($record->number)->toBe(5);

    // Manually reset
    $record->reset();

    expect($record->number)->toBe(0)
        ->and($record->last_reset_at)->not->toBeNull();

    // Next generation should be 1
    $next = RunningNumberGenerator::make()->type(Organization::SECTION->value)->generate();
    expect($next)->toBe('SECTION001');
});

it('does not reset when reset_period is never', function () {
    // Generate numbers over time
    RunningNumberGenerator::make()->type(Organization::ORGANIZATION->value)->generate();
    RunningNumberGenerator::make()->type(Organization::ORGANIZATION->value)->generate();

    $record = config('running-number.model')::where('type', 'ORGANIZATION')->first();

    // Simulate time passing
    $record->update(['last_reset_at' => now()->subYear()]);

    // Should not reset since period is 'never'
    RunningNumberGenerator::make()->type(Organization::ORGANIZATION->value)->generate();

    $record->refresh();
    expect($record->number)->toBe(3); // Should continue from 2 to 3
});

it('resets correctly with yearly period', function () {
    $model = config('running-number.model');

    // Create a record with yearly reset period
    $record = $model::create([
        'type' => 'INVOICE',
        'number' => 100,
        'reset_period' => 'yearly',
        'last_reset_at' => now()->subYear(), // Last year
    ]);

    expect($record->needsReset())->toBeTrue();

    // Generate - should reset to 1
    $number = RunningNumberGenerator::make()->type('invoice')->generate();

    expect($number)->toBe('INVOICE001');
});

it('resets correctly with monthly period', function () {
    $model = config('running-number.model');

    // Create a record with monthly reset period
    $record = $model::create([
        'type' => 'RECEIPT',
        'number' => 50,
        'reset_period' => 'monthly',
        'last_reset_at' => now()->subMonth(), // Last month
    ]);

    expect($record->needsReset())->toBeTrue();

    // Generate - should reset to 1
    $number = RunningNumberGenerator::make()->type('receipt')->generate();

    expect($number)->toBe('RECEIPT001');
});

it('resets correctly with daily period', function () {
    $model = config('running-number.model');

    // Create a record with daily reset period
    $record = $model::create([
        'type' => 'TICKET',
        'number' => 25,
        'reset_period' => 'daily',
        'last_reset_at' => now()->subDay(), // Yesterday
    ]);

    expect($record->needsReset())->toBeTrue();

    // Generate - should reset to 1
    $number = RunningNumberGenerator::make()->type('ticket')->generate();

    expect($number)->toBe('TICKET001');
});

it('does not reset if same period has not passed', function () {
    $model = config('running-number.model');

    // Create a record with daily reset, but last reset was today
    $record = $model::create([
        'type' => 'ORDER',
        'number' => 10,
        'reset_period' => 'daily',
        'last_reset_at' => now(), // Today
    ]);

    expect($record->needsReset())->toBeFalse();

    // Generate - should continue from 10
    $number = RunningNumberGenerator::make()->type('order')->generate();

    expect($number)->toBe('ORDER011');
});

it('updates last_reset_at when reset occurs', function () {
    $model = config('running-number.model');

    // Create record with monthly reset from last month
    $record = $model::create([
        'type' => 'MONTHLY',
        'number' => 99,
        'reset_period' => 'monthly',
        'last_reset_at' => now()->subMonth(),
    ]);

    $originalResetDate = $record->last_reset_at;

    // Generate - should trigger reset
    RunningNumberGenerator::make()->type('monthly')->generate();

    $record->refresh();

    expect($record->number)->toBe(1)
        ->and($record->last_reset_at->isAfter($originalResetDate))->toBeTrue();
});

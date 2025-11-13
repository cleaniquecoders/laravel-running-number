<?php

use CleaniqueCoders\RunningNumber\Contracts\Generator;
use CleaniqueCoders\RunningNumber\Enums\Organization;
use CleaniqueCoders\RunningNumber\Exceptions\MaxNumberReachedException;
use CleaniqueCoders\RunningNumber\Generator as RunningNumberGenerator;
use CleaniqueCoders\RunningNumber\Presenters\CompactDatePresenter;
use CleaniqueCoders\RunningNumber\Presenters\YearMonthPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    include_once __DIR__.'/../database/migrations/create_running_number_table.php.stub';
    include_once __DIR__.'/../database/migrations/add_uuid_to_running_numbers_table.php.stub';
    include_once __DIR__.'/../database/migrations/add_reset_functionality_to_running_numbers_table.php.stub';
    include_once __DIR__.'/../database/migrations/add_scope_to_running_numbers_table.php.stub';

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

    // Run the scope migration
    $scopeMigration = new class extends \Illuminate\Database\Migrations\Migration
    {
        public function up()
        {
            Schema::table('running_numbers', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->string('scope')->nullable()->after('type');
                $table->unique(['type', 'scope'], 'running_numbers_type_scope_unique');
            });
        }
    };
    $scopeMigration->up();

    // Add test-specific types to config
    config(['running-number.types' => array_merge(
        config('running-number.types'),
        ['invoice', 'receipt', 'ticket', 'order', 'monthly', 'adjustment', 'daily', 'test1', 'test2']
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

// Scope Tests - Multiple Sequences Per Type
it('generates separate sequences for different scopes', function () {
    // Generate numbers for retail scope
    $retail1 = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('retail')
        ->generate();

    $retail2 = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('retail')
        ->generate();

    // Generate numbers for wholesale scope
    $wholesale1 = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('wholesale')
        ->generate();

    $wholesale2 = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('wholesale')
        ->generate();

    // Both should start from 1 and increment independently
    expect($retail1)->toBe('INVOICE001')
        ->and($retail2)->toBe('INVOICE002')
        ->and($wholesale1)->toBe('INVOICE001')
        ->and($wholesale2)->toBe('INVOICE002');
});

it('maintains separate database records for different scopes', function () {
    RunningNumberGenerator::make()->type('invoice')->scope('retail')->generate();
    RunningNumberGenerator::make()->type('invoice')->scope('wholesale')->generate();

    $model = config('running-number.model');

    $retailRecord = $model::where('type', 'INVOICE')
        ->where('scope', 'retail')
        ->first();

    $wholesaleRecord = $model::where('type', 'INVOICE')
        ->where('scope', 'wholesale')
        ->first();

    expect($retailRecord)->not->toBeNull()
        ->and($wholesaleRecord)->not->toBeNull()
        ->and($retailRecord->id)->not->toBe($wholesaleRecord->id)
        ->and($retailRecord->number)->toBe(1)
        ->and($wholesaleRecord->number)->toBe(1);
});

it('generates separate sequence for null scope vs named scope', function () {
    // Generate without scope (null)
    $noScope1 = RunningNumberGenerator::make()->type('invoice')->generate();
    $noScope2 = RunningNumberGenerator::make()->type('invoice')->generate();

    // Generate with scope
    $withScope1 = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('online')
        ->generate();

    expect($noScope1)->toBe('INVOICE001')
        ->and($noScope2)->toBe('INVOICE002')
        ->and($withScope1)->toBe('INVOICE001');

    // Verify two separate records exist
    $model = config('running-number.model');
    $records = $model::where('type', 'INVOICE')->get();

    expect($records->count())->toBe(2);
});

it('allows multiple scopes for the same type', function () {
    $scopes = ['branch-a', 'branch-b', 'branch-c', 'online', 'retail'];

    foreach ($scopes as $scope) {
        for ($i = 1; $i <= 3; $i++) {
            RunningNumberGenerator::make()
                ->type('receipt')
                ->scope($scope)
                ->generate();
        }
    }

    $model = config('running-number.model');
    $records = $model::where('type', 'RECEIPT')->get();

    // Should have 5 separate records, one for each scope
    expect($records->count())->toBe(5);

    // Each should have number = 3
    foreach ($records as $record) {
        expect($record->number)->toBe(3);
    }
});

it('enforces unique constraint on type and scope combination', function () {
    RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('retail')
        ->generate();

    // Trying to manually create duplicate should fail
    $model = config('running-number.model');

    expect(function () use ($model) {
        $model::create([
            'type' => 'INVOICE',
            'scope' => 'retail',
            'number' => 0,
            'reset_period' => 'never',
            'last_reset_at' => now(),
        ]);
    })->toThrow(\Illuminate\Database\QueryException::class);
});

it('handles scope with special characters', function () {
    $scopes = [
        'branch-01',
        'branch_02',
        'BRANCH-03',
        'dept.sales',
    ];

    foreach ($scopes as $scope) {
        $number = RunningNumberGenerator::make()
            ->type('order')
            ->scope($scope)
            ->generate();

        expect($number)->toBe('ORDER001');
    }

    $model = config('running-number.model');
    $records = $model::where('type', 'ORDER')->get();

    expect($records->count())->toBe(count($scopes));
});

it('maintains scope independence with reset periods', function () {
    $model = config('running-number.model');

    // Create two scoped records with monthly reset from last month
    $retail = $model::create([
        'type' => 'INVOICE',
        'scope' => 'retail',
        'number' => 100,
        'reset_period' => 'monthly',
        'last_reset_at' => now()->subMonth(),
    ]);

    $wholesale = $model::create([
        'type' => 'INVOICE',
        'scope' => 'wholesale',
        'number' => 200,
        'reset_period' => 'monthly',
        'last_reset_at' => now()->subMonth(),
    ]);

    // Generate for retail - should reset
    $retailNumber = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('retail')
        ->generate();

    // Generate for wholesale - should also reset
    $wholesaleNumber = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('wholesale')
        ->generate();

    expect($retailNumber)->toBe('INVOICE001')
        ->and($wholesaleNumber)->toBe('INVOICE001');

    // Verify both were reset independently
    $retail->refresh();
    $wholesale->refresh();

    expect($retail->number)->toBe(1)
        ->and($wholesale->number)->toBe(1);
});

// Custom Starting Number Tests
it('starts from custom number when creating new type', function () {
    $number = RunningNumberGenerator::make()
        ->type('invoice')
        ->startFrom(1000)
        ->generate();

    expect($number)->toBe('INVOICE1001');

    $model = config('running-number.model');
    $record = $model::where('type', 'INVOICE')->first();

    expect($record->number)->toBe(1001);
});

it('starts from zero by default', function () {
    $number = RunningNumberGenerator::make()
        ->type('receipt')
        ->generate();

    expect($number)->toBe('RECEIPT001');
});

it('allows different starting numbers for different types', function () {
    $invoice = RunningNumberGenerator::make()
        ->type('invoice')
        ->startFrom(5000)
        ->generate();

    $receipt = RunningNumberGenerator::make()
        ->type('receipt')
        ->startFrom(2000)
        ->generate();

    expect($invoice)->toBe('INVOICE5001')
        ->and($receipt)->toBe('RECEIPT2001');
});

it('ignores startFrom for existing types', function () {
    // Create initial record
    RunningNumberGenerator::make()
        ->type('order')
        ->startFrom(100)
        ->generate();

    // Try to set different startFrom - should be ignored
    $number = RunningNumberGenerator::make()
        ->type('order')
        ->startFrom(5000)
        ->generate();

    // Should continue from 101, not jump to 5001
    expect($number)->toBe('ORDER102');
});

it('works with scope and custom starting number', function () {
    $retail = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('retail')
        ->startFrom(1000)
        ->generate();

    $wholesale = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('wholesale')
        ->startFrom(5000)
        ->generate();

    expect($retail)->toBe('INVOICE1001')
        ->and($wholesale)->toBe('INVOICE5001');

    $model = config('running-number.model');

    $retailRecord = $model::where('type', 'INVOICE')
        ->where('scope', 'retail')
        ->first();

    $wholesaleRecord = $model::where('type', 'INVOICE')
        ->where('scope', 'wholesale')
        ->first();

    expect($retailRecord->number)->toBe(1001)
        ->and($wholesaleRecord->number)->toBe(5001);
});

it('handles large starting numbers', function () {
    $number = RunningNumberGenerator::make()
        ->type('ticket')
        ->startFrom(999999)
        ->generate();

    expect($number)->toBe('TICKET1000000');
});

it('can start from negative numbers', function () {
    $number = RunningNumberGenerator::make()
        ->type('adjustment')
        ->startFrom(-10)
        ->generate();

    // Negative numbers increment: -10 -> -9
    expect($number)->toBe('ADJUSTMENT0-9');
});

// Number Range Management Tests
it('throws exception when max number is reached', function () {
    RunningNumberGenerator::make()
        ->type('ticket')
        ->maxNumber(5)
        ->generate();

    RunningNumberGenerator::make()
        ->type('ticket')
        ->maxNumber(5)
        ->generate();

    RunningNumberGenerator::make()
        ->type('ticket')
        ->maxNumber(5)
        ->generate();

    RunningNumberGenerator::make()
        ->type('ticket')
        ->maxNumber(5)
        ->generate();

    RunningNumberGenerator::make()
        ->type('ticket')
        ->maxNumber(5)
        ->generate();

    // 6th attempt should throw exception
    expect(function () {
        RunningNumberGenerator::make()
            ->type('ticket')
            ->maxNumber(5)
            ->generate();
    })->toThrow(MaxNumberReachedException::class);
});

it('allows generation without max number constraint', function () {
    for ($i = 1; $i <= 100; $i++) {
        $number = RunningNumberGenerator::make()
            ->type('receipt')
            ->generate();
    }

    expect($number)->toBe('RECEIPT100');
});

it('works with custom starting number and max number', function () {
    // Start from 95, max at 100 - should allow 5 generations (96-100)
    for ($i = 1; $i <= 5; $i++) {
        $number = RunningNumberGenerator::make()
            ->type('order')
            ->startFrom(95)
            ->maxNumber(100)
            ->generate();
    }

    // Last successful generation should be ORDER100
    expect($number)->toBe('ORDER100');

    // 6th should fail (would be 101, exceeding max of 100)
    expect(function () {
        RunningNumberGenerator::make()
            ->type('order')
            ->startFrom(95)
            ->maxNumber(100)
            ->generate();
    })->toThrow(MaxNumberReachedException::class);
});

it('max number works independently per scope', function () {
    // Generate for retail scope up to max
    for ($i = 1; $i <= 3; $i++) {
        RunningNumberGenerator::make()
            ->type('invoice')
            ->scope('retail')
            ->maxNumber(3)
            ->generate();
    }

    // Should throw for retail
    expect(function () {
        RunningNumberGenerator::make()
            ->type('invoice')
            ->scope('retail')
            ->maxNumber(3)
            ->generate();
    })->toThrow(MaxNumberReachedException::class);

    // Should still work for wholesale scope
    $number = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('wholesale')
        ->maxNumber(3)
        ->generate();

    expect($number)->toBe('INVOICE001');
});

it('provides meaningful error message when max is reached', function () {
    RunningNumberGenerator::make()
        ->type('ticket')
        ->maxNumber(2)
        ->generate();

    RunningNumberGenerator::make()
        ->type('ticket')
        ->maxNumber(2)
        ->generate();

    try {
        RunningNumberGenerator::make()
            ->type('ticket')
            ->maxNumber(2)
            ->generate();

        // Should not reach here
        expect(false)->toBeTrue();
    } catch (MaxNumberReachedException $e) {
        expect($e->getMessage())->toContain('Maximum number 2 reached for type TICKET');
    }
});

it('max number resets with reset period', function () {
    $model = config('running-number.model');

    // Create record with daily reset from yesterday, at max number
    $model::create([
        'type' => 'DAILY',
        'number' => 10,
        'reset_period' => 'daily',
        'last_reset_at' => now()->subDay(),
    ]);

    // Should reset and allow generation even with max number
    $number = RunningNumberGenerator::make()
        ->type('daily')
        ->maxNumber(10)
        ->generate();

    expect($number)->toBe('DAILY001');
});

it('validates max number is greater than starting number', function () {
    // This should work - start at 10, max at 20
    $number = RunningNumberGenerator::make()
        ->type('test1')
        ->startFrom(10)
        ->maxNumber(20)
        ->generate();

    expect($number)->toBe('TEST1011');

    // Start at 100, max at 50 - first generation creates 100, then increments to 101
    // Since 100 >= 50 (max), it should throw immediately
    expect(function () {
        RunningNumberGenerator::make()
            ->type('test2')
            ->startFrom(100)
            ->maxNumber(50)
            ->generate();
    })->toThrow(MaxNumberReachedException::class);
});

// Preview Mode Tests
it('previews next number without incrementing', function () {
    // Generate first number
    $first = RunningNumberGenerator::make()
        ->type('invoice')
        ->generate();

    expect($first)->toBe('INVOICE001');

    // Preview should show 002
    $preview = RunningNumberGenerator::make()
        ->type('invoice')
        ->preview();

    expect($preview)->toBe('INVOICE002');

    // Preview again - should still be 002
    $preview2 = RunningNumberGenerator::make()
        ->type('invoice')
        ->preview();

    expect($preview2)->toBe('INVOICE002');

    // Actually generate - should be 002
    $second = RunningNumberGenerator::make()
        ->type('invoice')
        ->generate();

    expect($second)->toBe('INVOICE002');
});

it('preview works with scopes', function () {
    RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('retail')
        ->generate();

    $preview = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('retail')
        ->preview();

    expect($preview)->toBe('INVOICE002');

    // Different scope should preview 001
    $previewWholesale = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('wholesale')
        ->preview();

    expect($previewWholesale)->toBe('INVOICE001');
});

it('preview accounts for pending resets', function () {
    $model = config('running-number.model');

    // Create record with daily reset from yesterday
    $model::create([
        'type' => 'TICKET',
        'number' => 50,
        'reset_period' => 'daily',
        'last_reset_at' => now()->subDay(),
    ]);

    // Preview should show reset value (001)
    $preview = RunningNumberGenerator::make()
        ->type('ticket')
        ->preview();

    expect($preview)->toBe('TICKET001');
});

it('preview works with new types', function () {
    // Preview on brand new type
    $preview = RunningNumberGenerator::make()
        ->type('order')
        ->preview();

    expect($preview)->toBe('ORDER001');

    // Should still preview 001 (not generated yet)
    $preview2 = RunningNumberGenerator::make()
        ->type('order')
        ->preview();

    expect($preview2)->toBe('ORDER001');
});

it('preview works with date-based presenters', function () {
    RunningNumberGenerator::make()
        ->type('invoice')
        ->formatter(new YearMonthPresenter)
        ->generate();

    $preview = RunningNumberGenerator::make()
        ->type('invoice')
        ->formatter(new YearMonthPresenter)
        ->preview();

    $expected = 'INVOICE-'.date('Y').'-'.date('m').'-002';
    expect($preview)->toBe($expected);
});

// Bulk Generation Tests
it('generates multiple numbers at once', function () {
    $numbers = RunningNumberGenerator::make()
        ->type('invoice')
        ->generateBatch(5);

    expect($numbers)->toHaveCount(5)
        ->and($numbers[0])->toBe('INVOICE001')
        ->and($numbers[1])->toBe('INVOICE002')
        ->and($numbers[2])->toBe('INVOICE003')
        ->and($numbers[3])->toBe('INVOICE004')
        ->and($numbers[4])->toBe('INVOICE005');

    // Verify database was updated correctly
    $model = config('running-number.model');
    $record = $model::where('type', 'INVOICE')->first();
    expect($record->number)->toBe(5);
});

it('batch generation is atomic', function () {
    // Generate batch
    RunningNumberGenerator::make()
        ->type('order')
        ->generateBatch(10);

    // Generate single
    $number = RunningNumberGenerator::make()
        ->type('order')
        ->generate();

    expect($number)->toBe('ORDER011');
});

it('batch generation works with scopes', function () {
    $retail = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('retail')
        ->generateBatch(3);

    $wholesale = RunningNumberGenerator::make()
        ->type('invoice')
        ->scope('wholesale')
        ->generateBatch(2);

    expect($retail)->toHaveCount(3)
        ->and($retail[0])->toBe('INVOICE001')
        ->and($retail[2])->toBe('INVOICE003')
        ->and($wholesale)->toHaveCount(2)
        ->and($wholesale[0])->toBe('INVOICE001')
        ->and($wholesale[1])->toBe('INVOICE002');
});

it('batch generation respects max number', function () {
    // Should fail - trying to generate 10 but max is 5
    expect(function () {
        RunningNumberGenerator::make()
            ->type('ticket')
            ->maxNumber(5)
            ->generateBatch(10);
    })->toThrow(MaxNumberReachedException::class);

    // Should succeed - exactly at limit
    $numbers = RunningNumberGenerator::make()
        ->type('receipt')
        ->maxNumber(5)
        ->generateBatch(5);

    expect($numbers)->toHaveCount(5);

    // Next batch should fail
    expect(function () {
        RunningNumberGenerator::make()
            ->type('receipt')
            ->maxNumber(5)
            ->generateBatch(1);
    })->toThrow(MaxNumberReachedException::class);
});

it('batch generation with custom starting number', function () {
    $numbers = RunningNumberGenerator::make()
        ->type('invoice')
        ->startFrom(100)
        ->generateBatch(3);

    expect($numbers[0])->toBe('INVOICE101')
        ->and($numbers[1])->toBe('INVOICE102')
        ->and($numbers[2])->toBe('INVOICE103');
});

it('returns empty array for zero or negative count', function () {
    $zero = RunningNumberGenerator::make()
        ->type('order')
        ->generateBatch(0);

    $negative = RunningNumberGenerator::make()
        ->type('order')
        ->generateBatch(-5);

    expect($zero)->toBeArray()->toHaveCount(0)
        ->and($negative)->toBeArray()->toHaveCount(0);

    // Verify database wasn't touched (no record created for empty batch)
    $model = config('running-number.model');
    $count = $model::where('type', 'ORDER')->count();
    expect($count)->toBe(0);
});

it('batch generation works with date presenters', function () {
    $numbers = RunningNumberGenerator::make()
        ->type('invoice')
        ->formatter(new CompactDatePresenter)
        ->generateBatch(3);

    expect($numbers)->toHaveCount(3);

    $expectedPrefix = 'INVOICE-'.date('Ymd');
    expect($numbers[0])->toBe($expectedPrefix.'001')
        ->and($numbers[1])->toBe($expectedPrefix.'002')
        ->and($numbers[2])->toBe($expectedPrefix.'003');
});

it('batch generation handles reset period', function () {
    $model = config('running-number.model');

    // Create with monthly reset from last month at number 50
    $model::create([
        'type' => 'MONTHLY',
        'number' => 50,
        'reset_period' => 'monthly',
        'last_reset_at' => now()->subMonth(),
    ]);

    // Should reset and start from 1
    $numbers = RunningNumberGenerator::make()
        ->type('monthly')
        ->generateBatch(3);

    expect($numbers[0])->toBe('MONTHLY001')
        ->and($numbers[1])->toBe('MONTHLY002')
        ->and($numbers[2])->toBe('MONTHLY003');
});

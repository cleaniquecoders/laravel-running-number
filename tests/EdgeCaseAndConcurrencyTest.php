<?php

use CleaniqueCoders\RunningNumber\Exceptions\ConfigurationException;
use CleaniqueCoders\RunningNumber\Exceptions\InvalidRunningNumberTypeException;
use CleaniqueCoders\RunningNumber\Exceptions\MaxNumberReachedException;
use CleaniqueCoders\RunningNumber\Generator as RunningNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

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
        ['test', 'stress', 'edge', 'concurrent']
    )]);
});

describe('Input Validation', function () {
    it('throws exception when type is not set before generating', function () {
        expect(fn () => RunningNumberGenerator::make()->generate())
            ->toThrow(InvalidRunningNumberTypeException::class, 'Running number type must be set before generating');
    });

    it('throws exception when type is empty string', function () {
        expect(fn () => RunningNumberGenerator::make()->type('')->generate())
            ->toThrow(ConfigurationException::class, 'Running number type cannot be empty');
    });

    it('throws exception when type has only whitespace', function () {
        expect(fn () => RunningNumberGenerator::make()->type('   ')->generate())
            ->toThrow(ConfigurationException::class, 'Running number type cannot be empty');
    });

    it('throws exception when type is not in allowed types list', function () {
        expect(fn () => RunningNumberGenerator::make()->type('INVALID_TYPE')->generate())
            ->toThrow(InvalidRunningNumberTypeException::class, 'Unsupported running number type "INVALID_TYPE"');
    });

    it('throws exception when maxNumber is zero', function () {
        expect(fn () => RunningNumberGenerator::make()->type('test')->maxNumber(0))
            ->toThrow(ConfigurationException::class, 'Maximum number must be greater than 0');
    });

    it('throws exception when maxNumber is negative', function () {
        expect(fn () => RunningNumberGenerator::make()->type('test')->maxNumber(-10))
            ->toThrow(ConfigurationException::class, 'Maximum number must be greater than 0');
    });
});

describe('Edge Cases', function () {
    it('handles very large numbers correctly', function () {
        $generator = RunningNumberGenerator::make()->type('stress');

        // Generate first number
        $first = $generator->generate();
        expect($first)->toBe('STRESS001');

        // Manually set to very large number
        $model = config('running-number.model')::where('type', 'STRESS')->first();
        $model->number = 999999999;
        $model->save();

        // Generate next
        $large = RunningNumberGenerator::make()->type('stress')->generate();
        expect($large)->toContain('1000000000');
    });

    it('handles negative starting numbers', function () {
        $number = RunningNumberGenerator::make()
            ->type('edge')
            ->startFrom(-5)
            ->generate();

        // Starting from -5, first generate() increments to -4
        expect($number)->toBe('EDGE0-4');
    });

    it('handles zero as starting number', function () {
        $number = RunningNumberGenerator::make()
            ->type('edge')
            ->scope('zero')
            ->startFrom(0)
            ->generate();

        expect($number)->toBe('EDGE001');
    });

    it('handles generateBatch with count of 0', function () {
        $numbers = RunningNumberGenerator::make()
            ->type('test')
            ->generateBatch(0);

        expect($numbers)->toBe([]);
    });

    it('handles generateBatch with count of 1', function () {
        $numbers = RunningNumberGenerator::make()
            ->type('test')
            ->generateBatch(1);

        expect($numbers)->toHaveCount(1)
            ->and($numbers[0])->toBe('TEST001');
    });

    it('handles very large batch generation', function () {
        $numbers = RunningNumberGenerator::make()
            ->type('stress')
            ->generateBatch(1000);

        expect($numbers)->toHaveCount(1000)
            ->and($numbers[0])->toBe('STRESS001')
            ->and($numbers[999])->toBe('STRESS1000');
    });

    it('respects uppercase setting for lowercase type', function () {
        $number = RunningNumberGenerator::make()
            ->type('test')
            ->toUpperCase(false)
            ->generate();

        expect($number)->toBe('test001');
    });

    it('handles scope with special characters', function () {
        $number = RunningNumberGenerator::make()
            ->type('test')
            ->scope('vip-customer-2024')
            ->generate();

        expect($number)->toBe('TEST001');

        $model = config('running-number.model')::where('type', 'TEST')
            ->where('scope', 'vip-customer-2024')
            ->first();

        expect($model)->not->toBeNull()
            ->and($model->scope)->toBe('vip-customer-2024');
    });

    it('throws MaxNumberReachedException with descriptive message', function () {
        RunningNumberGenerator::make()
            ->type('test')
            ->maxNumber(5)
            ->generate(); // 1

        RunningNumberGenerator::make()
            ->type('test')
            ->maxNumber(5)
            ->generate(); // 2

        RunningNumberGenerator::make()
            ->type('test')
            ->maxNumber(5)
            ->generate(); // 3

        RunningNumberGenerator::make()
            ->type('test')
            ->maxNumber(5)
            ->generate(); // 4

        RunningNumberGenerator::make()
            ->type('test')
            ->maxNumber(5)
            ->generate(); // 5

        expect(fn () => RunningNumberGenerator::make()
            ->type('test')
            ->maxNumber(5)
            ->generate())
            ->toThrow(MaxNumberReachedException::class, 'Maximum number 5 reached for running number type "TEST"');
    });
});

describe('Concurrency Tests', function () {
    it('handles concurrent generation without duplicates', function () {
        $numbers = [];

        // Simulate 10 concurrent requests
        for ($i = 0; $i < 10; $i++) {
            $numbers[] = RunningNumberGenerator::make()
                ->type('concurrent')
                ->generate();
        }

        // All numbers should be unique
        expect(count($numbers))->toBe(10)
            ->and(count(array_unique($numbers)))->toBe(10)
            ->and($numbers)->toContain('CONCURRENT001')
            ->and($numbers)->toContain('CONCURRENT010');
    });

    it('prevents race conditions with multiple scopes', function () {
        $results = [];

        // Generate numbers for two different scopes concurrently
        for ($i = 0; $i < 5; $i++) {
            $results['scope1'][] = RunningNumberGenerator::make()
                ->type('concurrent')
                ->scope('scope1')
                ->generate();

            $results['scope2'][] = RunningNumberGenerator::make()
                ->type('concurrent')
                ->scope('scope2')
                ->generate();
        }

        // Each scope should have unique sequential numbers
        expect(count(array_unique($results['scope1'])))->toBe(5)
            ->and(count(array_unique($results['scope2'])))->toBe(5)
            ->and($results['scope1'][0])->toBe('CONCURRENT001')
            ->and($results['scope2'][0])->toBe('CONCURRENT001');
    });

    it('handles concurrent batch generation', function () {
        $batch1 = RunningNumberGenerator::make()
            ->type('concurrent')
            ->scope('batch1')
            ->generateBatch(5);

        $batch2 = RunningNumberGenerator::make()
            ->type('concurrent')
            ->scope('batch2')
            ->generateBatch(5);

        // Each batch should have its own sequential numbers
        expect($batch1)->toHaveCount(5)
            ->and($batch2)->toHaveCount(5)
            ->and($batch1[0])->toBe('CONCURRENT001')
            ->and($batch1[4])->toBe('CONCURRENT005')
            ->and($batch2[0])->toBe('CONCURRENT001')
            ->and($batch2[4])->toBe('CONCURRENT005');
    });

    it('maintains sequence integrity under load', function () {
        // Generate 100 numbers rapidly
        $numbers = [];
        for ($i = 0; $i < 100; $i++) {
            $numbers[] = RunningNumberGenerator::make()
                ->type('stress')
                ->generate();
        }

        // Verify no gaps or duplicates
        expect(count($numbers))->toBe(100)
            ->and(count(array_unique($numbers)))->toBe(100);

        // Verify final count in database
        $model = config('running-number.model')::where('type', 'STRESS')->first();
        expect($model->number)->toBe(100);
    });

    it('handles concurrent generation with max number limit', function () {
        $generated = [];
        $errors = 0;

        // Try to generate 10 numbers with max of 5
        for ($i = 0; $i < 10; $i++) {
            try {
                $generated[] = RunningNumberGenerator::make()
                    ->type('concurrent')
                    ->scope('limited')
                    ->maxNumber(5)
                    ->generate();
            } catch (MaxNumberReachedException $e) {
                $errors++;
            }
        }

        // Should generate 5 numbers and throw 5 exceptions
        expect(count($generated))->toBe(5)
            ->and($errors)->toBe(5)
            ->and(count(array_unique($generated)))->toBe(5);
    });

    it('handles concurrent preview without affecting counter', function () {
        // Generate one number first
        $first = RunningNumberGenerator::make()
            ->type('concurrent')
            ->scope('preview')
            ->generate();

        expect($first)->toBe('CONCURRENT001');

        // Multiple concurrent previews should all show same next number
        $previews = [];
        for ($i = 0; $i < 10; $i++) {
            $previews[] = RunningNumberGenerator::make()
                ->type('concurrent')
                ->scope('preview')
                ->preview();
        }

        // All previews should be identical
        expect(count(array_unique($previews)))->toBe(1)
            ->and($previews[0])->toBe('CONCURRENT002');

        // Generate next number - should still be 002
        $second = RunningNumberGenerator::make()
            ->type('concurrent')
            ->scope('preview')
            ->generate();

        expect($second)->toBe('CONCURRENT002');
    });
});

describe('Configuration Edge Cases', function () {
    it('handles missing presenter configuration gracefully', function () {
        config(['running-number.presenter' => null]);

        expect(fn () => RunningNumberGenerator::make())
            ->toThrow(ConfigurationException::class, 'Invalid presenter configuration');
    });

    it('handles invalid presenter class', function () {
        config(['running-number.presenter' => 'NonExistentClass']);

        expect(fn () => RunningNumberGenerator::make())
            ->toThrow(ConfigurationException::class, 'Invalid presenter configuration');
    });

    it('handles missing model configuration', function () {
        config(['running-number.model' => null]);

        expect(fn () => RunningNumberGenerator::make()->type('test')->generate())
            ->toThrow(ConfigurationException::class, 'Invalid model configuration');
    });

    it('handles invalid types configuration', function () {
        config(['running-number.types' => 'not-an-array']);

        expect(fn () => RunningNumberGenerator::make()->type('test')->generate())
            ->toThrow(ConfigurationException::class, 'Configuration "running-number.types" must be an array');
    });
});

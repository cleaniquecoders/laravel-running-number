<?php

use CleaniqueCoders\RunningNumber\Console\Commands\CreateRunningNumberCommand;
use CleaniqueCoders\RunningNumber\Console\Commands\ListRunningNumbersCommand;
use CleaniqueCoders\RunningNumber\Console\Commands\ResetRunningNumberCommand;
use CleaniqueCoders\RunningNumber\Events\RunningNumberGenerated;
use CleaniqueCoders\RunningNumber\Models\RunningNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    include_once __DIR__.'/../database/migrations/create_running_number_table.php.stub';
    include_once __DIR__.'/../database/migrations/add_uuid_to_running_numbers_table.php.stub';
    include_once __DIR__.'/../database/migrations/add_reset_functionality_to_running_numbers_table.php.stub';
    include_once __DIR__.'/../database/migrations/add_scope_to_running_numbers_table.php.stub';

    (new \CreateRunningNumberTable)->up();

    Schema::table('running_numbers', function (\Illuminate\Database\Schema\Blueprint $table) {
        $table->uuid('uuid')->nullable()->unique()->after('id');
    });

    Schema::table('running_numbers', function (\Illuminate\Database\Schema\Blueprint $table) {
        $table->string('reset_period')->default('never')->after('type');
        $table->timestamp('last_reset_at')->nullable()->after('reset_period');
    });

    Schema::table('running_numbers', function (\Illuminate\Database\Schema\Blueprint $table) {
        $table->string('scope')->nullable()->after('type');
        $table->unique(['type', 'scope'], 'running_numbers_type_scope_unique');
    });

    config(['running-number.types' => ['invoice', 'order', 'ticket', 'test']]);
});

describe('Artisan Commands', function () {
    it('can list running numbers', function () {
        running_number()->type('invoice')->generate();
        running_number()->type('order')->scope('retail')->generate();

        Artisan::call(ListRunningNumbersCommand::class);
        $output = Artisan::output();

        expect($output)->toContain('INVOICE') // Types are stored in uppercase
            ->and($output)->toContain('ORDER')
            ->and($output)->toContain('retail');
    });

    it('can filter list by type', function () {
        running_number()->type('invoice')->generate();
        running_number()->type('order')->generate();

        Artisan::call(ListRunningNumbersCommand::class, ['--type' => 'INVOICE']); // Must use uppercase
        $output = Artisan::output();

        expect($output)->toContain('INVOICE');
    });

    it('can filter list by scope', function () {
        running_number()->type('order')->scope('retail')->generate();
        running_number()->type('order')->scope('wholesale')->generate();

        Artisan::call(ListRunningNumbersCommand::class, ['--scope' => 'retail']);
        $output = Artisan::output();

        expect($output)->toContain('retail');
    });

    it('can create a new running number', function () {
        Artisan::call(CreateRunningNumberCommand::class, [
            'type' => 'invoice',
            '--start' => 1000,
            '--reset' => 'monthly',
        ]);

        $output = Artisan::output();

        expect($output)->toContain('Successfully created')
            ->and($output)->toContain('invoice')
            ->and($output)->toContain('1000');

        $rn = RunningNumber::where('type', 'invoice')->first();
        expect($rn)->not->toBeNull()
            ->and($rn->number)->toBe(1000)
            ->and($rn->reset_period->value ?? $rn->reset_period)->toBe('monthly');
    });

    it('prevents creating duplicate running number', function () {
        running_number()->type('invoice')->generate();

        Artisan::call(CreateRunningNumberCommand::class, ['type' => 'INVOICE']); // Must use uppercase
        $output = Artisan::output();

        expect($output)->toContain('already exists');
    });

    it('can reset a running number', function () {
        running_number()->type('invoice')->generate();
        running_number()->type('invoice')->generate();
        running_number()->type('invoice')->generate();

        $rn = RunningNumber::where('type', 'INVOICE')->whereNull('scope')->first(); // Use uppercase
        expect($rn)->not->toBeNull()
            ->and($rn->number)->toBe(3);

        Artisan::call(ResetRunningNumberCommand::class, [
            'type' => 'INVOICE', // Must use uppercase
            '--force' => true,
        ]);

        $output = Artisan::output();
        expect($output)->toContain('Successfully reset');

        $rn->refresh();
        expect($rn->number)->toBe(0)
            ->and($rn->last_reset_at)->not->toBeNull();
    });

    it('can reset a running number with scope', function () {
        running_number()->type('order')->scope('retail')->generate();
        running_number()->type('order')->scope('retail')->generate();

        Artisan::call(ResetRunningNumberCommand::class, [
            'type' => 'ORDER', // Must use uppercase
            '--scope' => 'retail',
            '--force' => true,
        ]);

        $output = Artisan::output();
        expect($output)->toContain('Successfully reset');

        $rn = RunningNumber::where('type', 'ORDER')->where('scope', 'retail')->first(); // Use uppercase
        expect($rn->number)->toBe(0);
    });
    it('fails to reset non-existent running number', function () {
        Artisan::call(ResetRunningNumberCommand::class, [
            'type' => 'nonexistent',
            '--force' => true,
        ]);

        $output = Artisan::output();
        expect($output)->toContain('not found');
    });
});

describe('Events', function () {
    it('dispatches RunningNumberGenerated event', function () {
        Event::fake([RunningNumberGenerated::class]);

        $number = running_number()->type('invoice')->generate();

        Event::assertDispatched(RunningNumberGenerated::class, function ($event) use ($number) {
            return $event->type === 'INVOICE'
                && $event->formattedNumber === $number
                && $event->number === 1
                && $event->scope === null;
        });
    });

    it('event includes scope information', function () {
        Event::fake([RunningNumberGenerated::class]);

        running_number()->type('order')->scope('retail')->generate();

        Event::assertDispatched(RunningNumberGenerated::class, function ($event) {
            return $event->type === 'ORDER' && $event->scope === 'retail';
        });
    });

    it('event includes model instance', function () {
        $captured = null;

        Event::listen(RunningNumberGenerated::class, function ($event) use (&$captured) {
            $captured = $event;
        });

        running_number()->type('invoice')->generate();

        expect($captured)->not->toBeNull()
            ->and($captured->model)->toBeInstanceOf(RunningNumber::class)
            ->and($captured->model->type)->toBe('INVOICE') // Type is uppercased by config
            ->and($captured->model->uuid)->not->toBeNull();
    });

    it('event toArray method returns complete data', function () {
        $captured = null;

        Event::listen(RunningNumberGenerated::class, function ($event) use (&$captured) {
            $captured = $event;
        });

        running_number()->type('ticket')->generate();

        expect($captured)->not->toBeNull();

        $array = $captured->toArray();

        expect($array)->toHaveKeys(['type', 'number', 'formatted_number', 'uuid', 'reset_period']);
    });

    it('can listen to running number generated event', function () {
        $captured = null;

        Event::listen(RunningNumberGenerated::class, function ($event) use (&$captured) {
            $captured = $event;
        });

        $number = running_number()->type('invoice')->generate();

        expect($captured)->not->toBeNull()
            ->and($captured->formattedNumber)->toBe($number)
            ->and($captured->type)->toBe('INVOICE');
    });
});

<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

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

    // Configure types for testing
    Config::set('running-number.types', ['invoice', 'order', 'ticket']);

    // Enable API for testing
    Config::set('running-number.api.enabled', true);
    Config::set('running-number.api.prefix', 'api/running-numbers');
    Config::set('running-number.api.middleware', ['api']);

    // Register routes
    require __DIR__.'/../routes/api.php';
});

describe('API Endpoints', function () {
    it('can generate running number via API', function () {
        $response = $this->postJson('/api/running-numbers/generate', [
            'type' => 'invoice',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'number',
                    'type',
                    'scope',
                    'current_count',
                    'uuid',
                    'reset_period',
                    'created_at',
                ],
            ]);

        expect($response->json('data.number'))->toBe('INVOICE001');
        expect($response->json('data.type'))->toBe('INVOICE');
    });

    it('can generate with scope via API', function () {
        $response = $this->postJson('/api/running-numbers/generate', [
            'type' => 'order',
            'scope' => 'retail',
        ]);

        $response->assertStatus(201);
        expect($response->json('data.number'))->toBe('ORDER001');
        expect($response->json('data.scope'))->toBe('retail');
    });

    it('can generate with custom start number via API', function () {
        $response = $this->postJson('/api/running-numbers/generate', [
            'type' => 'ticket',
            'start_from' => 1000,
        ]);

        $response->assertStatus(201);
        expect($response->json('data.number'))->toBe('TICKET1001');
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/running-numbers/generate', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ])
            ->assertJsonValidationErrors(['type']);
    });

    it('handles invalid type gracefully', function () {
        $response = $this->postJson('/api/running-numbers/generate', [
            'type' => 'invalid-type',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid running number type',
            ]);
    });

    it('can get current running number info', function () {
        // Generate first
        running_number()->type('invoice')->generate();

        $response = $this->getJson('/api/running-numbers/current?type=invoice');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'uuid',
                    'type',
                    'scope',
                    'current_number',
                    'reset_period',
                    'created_at',
                    'updated_at',
                ],
            ]);

        expect($response->json('data.type'))->toBe('INVOICE');
        expect($response->json('data.current_number'))->toBe(1);
    });

    it('returns 404 when getting non-existent running number', function () {
        $response = $this->getJson('/api/running-numbers/current?type=nonexistent');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Running number not found',
            ]);
    });

    it('can preview next running number', function () {
        $response = $this->getJson('/api/running-numbers/preview?type=invoice');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'preview',
                    'type',
                    'scope',
                ],
            ]);

        expect($response->json('data.preview'))->toBe('INVOICE001');
    });

    it('can preview with scope', function () {
        $response = $this->getJson('/api/running-numbers/preview?type=order&scope=wholesale');

        $response->assertStatus(200);
        expect($response->json('data.preview'))->toBe('ORDER001');
        expect($response->json('data.scope'))->toBe('wholesale');
    });

    it('can list all running numbers', function () {
        // Create some running numbers
        running_number()->type('invoice')->generate();
        running_number()->type('order')->scope('retail')->generate();
        running_number()->type('ticket')->generate();

        $response = $this->getJson('/api/running-numbers/list');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'uuid',
                        'type',
                        'scope',
                        'current_number',
                        'reset_period',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);

        expect($response->json('meta.total'))->toBe(3);
    });

    it('can filter list by type', function () {
        running_number()->type('invoice')->generate();
        running_number()->type('order')->generate();

        $response = $this->getJson('/api/running-numbers/list?type=INVOICE');

        $response->assertStatus(200);
        expect($response->json('meta.total'))->toBe(1);
        expect($response->json('data.0.type'))->toBe('INVOICE');
    });

    it('can filter list by scope', function () {
        running_number()->type('order')->scope('retail')->generate();
        running_number()->type('order')->scope('wholesale')->generate();

        $response = $this->getJson('/api/running-numbers/list?scope=retail');

        $response->assertStatus(200);
        expect($response->json('meta.total'))->toBe(1);
        expect($response->json('data.0.scope'))->toBe('retail');
    });

    it('supports pagination', function () {
        // Create multiple running numbers
        for ($i = 0; $i < 20; $i++) {
            running_number()->type('ticket')->scope("dept-{$i}")->generate();
        }

        $response = $this->getJson('/api/running-numbers/list?per_page=5');

        $response->assertStatus(200);
        expect($response->json('meta.per_page'))->toBe(5);
        expect($response->json('meta.total'))->toBe(20);
        expect(count($response->json('data')))->toBe(5);
    });

    it('handles max number reached error', function () {
        // Generate up to max
        running_number()->type('invoice')->maxNumber(2)->generate();
        running_number()->type('invoice')->maxNumber(2)->generate();

        // Try to exceed max via API
        $response = $this->postJson('/api/running-numbers/generate', [
            'type' => 'invoice',
            'max_number' => 2,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Maximum number reached',
            ]);
    });

    it('returns consistent JSON structure for errors', function () {
        $response = $this->postJson('/api/running-numbers/generate', [
            'type' => 'invalid',
        ]);

        $response->assertJsonStructure([
            'success',
            'message',
            'error',
        ]);

        expect($response->json('success'))->toBe(false);
    });
});

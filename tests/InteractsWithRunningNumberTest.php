<?php

use CleaniqueCoders\RunningNumber\Concerns\InteractsWithRunningNumber;
use CleaniqueCoders\RunningNumber\Models\RunningNumber;
use CleaniqueCoders\RunningNumber\Presenters\YearMonthPresenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    include_once __DIR__.'/../database/migrations/create_running_number_table.php.stub';
    include_once __DIR__.'/../database/migrations/add_uuid_to_running_numbers_table.php.stub';
    include_once __DIR__.'/../database/migrations/add_reset_functionality_to_running_numbers_table.php.stub';
    include_once __DIR__.'/../database/migrations/add_scope_to_running_numbers_table.php.stub';

    (new \CreateRunningNumberTable)->up();

    // Run migrations
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

    // Add test types to config
    config(['running-number.types' => array_merge(
        config('running-number.types'),
        ['invoice', 'order', 'ticket', 'test']
    )]);

    // Add presenter configuration for invoice type
    config(['running-number.presenters.invoice' => \CleaniqueCoders\RunningNumber\Presenters\DatePrefixPresenter::class]);
});

describe('InteractsWithRunningNumber Trait', function () {
    it('auto-generates running number on model creation', function () {
        $model = new class extends Model
        {
            use InteractsWithRunningNumber;

            protected $table = 'test_models';

            protected $guarded = [];

            protected string $runningNumberField = 'invoice_number';

            protected string $runningNumberType = 'invoice';

            public function getConnectionName()
            {
                return null;
            }
        };

        // Create table for test model
        Schema::create('test_models', function ($table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->timestamps();
        });

        $instance = new $model;
        $instance->save();

        expect($instance->invoice_number)->toBe('INVOICE001');
        expect(RunningNumber::where('type', 'INVOICE')->count())->toBe(1); // Type is stored in uppercase
    });

    it('supports custom scope from model attribute', function () {
        $model = new class extends Model
        {
            use InteractsWithRunningNumber;

            protected $table = 'test_models';

            protected $guarded = [];

            protected string $runningNumberField = 'order_number';

            protected string $runningNumberType = 'order';

            protected string $runningNumberScope = '$tenant_id';

            public function getConnectionName()
            {
                return null;
            }
        };

        Schema::create('test_models', function ($table) {
            $table->id();
            $table->string('order_number')->nullable();
            $table->string('tenant_id');
            $table->timestamps();
        });

        $instance1 = new $model(['tenant_id' => 'tenant-a']);
        $instance1->save();

        $instance2 = new $model(['tenant_id' => 'tenant-b']);
        $instance2->save();

        $instance3 = new $model(['tenant_id' => 'tenant-a']);
        $instance3->save();

        expect($instance1->order_number)->toBe('ORDER001');
        expect($instance2->order_number)->toBe('ORDER001'); // Different scope
        expect($instance3->order_number)->toBe('ORDER002'); // Same scope as instance1
    });

    it('supports custom starting number', function () {
        $model = new class extends Model
        {
            use InteractsWithRunningNumber;

            protected $table = 'test_models';

            protected $guarded = [];

            protected string $runningNumberField = 'ticket_number';

            protected string $runningNumberType = 'ticket';

            protected int $runningNumberStart = 1000;

            public function getConnectionName()
            {
                return null;
            }
        };

        Schema::create('test_models', function ($table) {
            $table->id();
            $table->string('ticket_number')->nullable();
            $table->timestamps();
        });

        $instance = new $model;
        $instance->save();

        expect($instance->ticket_number)->toBe('TICKET1001');
    });

    it('supports max number limit', function () {
        $model = new class extends Model
        {
            use InteractsWithRunningNumber;

            protected $table = 'test_models';

            protected $guarded = [];

            protected string $runningNumberField = 'number';

            protected string $runningNumberType = 'test';

            protected int $runningNumberMax = 2;

            public function getConnectionName()
            {
                return null;
            }
        };

        Schema::create('test_models', function ($table) {
            $table->id();
            $table->string('number')->nullable();
            $table->timestamps();
        });

        $instance1 = new $model;
        $instance1->save();

        $instance2 = new $model;
        $instance2->save();

        expect($instance1->number)->toBe('TEST001');
        expect($instance2->number)->toBe('TEST002');

        // Should throw exception on third
        expect(fn () => (new $model)->save())
            ->toThrow(\CleaniqueCoders\RunningNumber\Exceptions\MaxNumberReachedException::class);
    });

    it('supports custom presenter', function () {
        $model = new class extends Model
        {
            use InteractsWithRunningNumber;

            protected $table = 'test_models';

            protected $guarded = [];

            protected string $runningNumberField = 'order_number';

            protected string $runningNumberType = 'order';

            protected string $runningNumberPresenter = YearMonthPresenter::class;

            public function getConnectionName()
            {
                return null;
            }
        };

        Schema::create('test_models', function ($table) {
            $table->id();
            $table->string('order_number')->nullable();
            $table->timestamps();
        });

        $instance = new $model;
        $instance->save();

        expect($instance->order_number)->toMatch('/ORDER-\d{4}-\d{2}-001/');
    });

    it('skips generation if field already has value', function () {
        $model = new class extends Model
        {
            use InteractsWithRunningNumber;

            protected $table = 'test_models';

            protected $guarded = [];

            protected string $runningNumberField = 'invoice_number';

            protected string $runningNumberType = 'invoice';

            public function getConnectionName()
            {
                return null;
            }
        };

        Schema::create('test_models', function ($table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->timestamps();
        });

        $instance = new $model(['invoice_number' => 'CUSTOM-001']);
        $instance->save();

        expect($instance->invoice_number)->toBe('CUSTOM-001');
        expect(RunningNumber::count())->toBe(0); // No running number generated
    });

    it('can preview next running number', function () {
        $model = new class extends Model
        {
            use InteractsWithRunningNumber;

            protected $table = 'test_models';

            protected $guarded = [];

            protected string $runningNumberField = 'invoice_number';

            protected string $runningNumberType = 'invoice';

            public function getConnectionName()
            {
                return null;
            }
        };

        Schema::create('test_models', function ($table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->timestamps();
        });

        $instance = new $model;

        $preview1 = $instance->previewRunningNumber();
        expect($preview1)->toBe('INVOICE001');

        $instance->save();
        expect($instance->invoice_number)->toBe('INVOICE001');

        $preview2 = $instance->previewRunningNumber();
        expect($preview2)->toBe('INVOICE002');
    });
});

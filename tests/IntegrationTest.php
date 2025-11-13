<?php

use CleaniqueCoders\RunningNumber\Contracts\Generator as GeneratorContract;
use CleaniqueCoders\RunningNumber\Enums\Organization;
use CleaniqueCoders\RunningNumber\Generator as RunningNumberGenerator;
use CleaniqueCoders\RunningNumber\Models\RunningNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
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
        ['invoice', 'order', 'ticket', 'test']
    )]);
});

describe('Service Container Binding', function () {
    it('can resolve Generator from container', function () {
        $generator = app(GeneratorContract::class);

        expect($generator)->toBeInstanceOf(GeneratorContract::class)
            ->and($generator)->toBeInstanceOf(RunningNumberGenerator::class);
    });

    it('can resolve Generator using singleton', function () {
        $generator1 = app('running-number');
        $generator2 = app('running-number');

        expect($generator1)->toBe($generator2)
            ->and($generator1)->toBeInstanceOf(GeneratorContract::class);
    });

    it('can inject Generator in constructor', function () {
        $service = new class(app(GeneratorContract::class)) {
            public function __construct(public GeneratorContract $generator) {}
        };

        expect($service->generator)->toBeInstanceOf(GeneratorContract::class);
    });

    it('uses configured generator class', function () {
        $customGenerator = new class extends RunningNumberGenerator {};

        config(['running-number.generator' => get_class($customGenerator)]);

        $generator = app(GeneratorContract::class);

        expect(get_class($generator))->toBe(get_class($customGenerator));
    });

    it('resolves new instance each time for Generator contract', function () {
        $generator1 = app(GeneratorContract::class);
        $generator2 = app(GeneratorContract::class);

        // Should be different instances
        expect($generator1)->not->toBe($generator2);
    });
});

describe('Model Observer Integration', function () {
    it('can generate running number in model observer', function () {
        // Create a test model with observer
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'test_models';
            protected $fillable = ['name', 'reference_number'];
            public $timestamps = false;

            protected static function booted()
            {
                static::creating(function ($model) {
                    if (empty($model->reference_number)) {
                        $model->reference_number = running_number()
                            ->type('test')
                            ->generate();
                    }
                });
            }
        };

        // Create the table
        Schema::create('test_models', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('reference_number');
        });

        // Create instance
        $instance = $model->newInstance();
        $instance->name = 'Test Item';
        $instance->save();

        expect($instance->reference_number)->toBe('TEST001');

        // Second instance should get next number
        $instance2 = $model->newInstance();
        $instance2->name = 'Test Item 2';
        $instance2->save();

        expect($instance2->reference_number)->toBe('TEST002');
    });

    it('generates unique numbers for multiple model creations', function () {
        Schema::create('invoices', function ($table) {
            $table->id();
            $table->string('invoice_number');
        });

        $invoiceModel = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'invoices';
            protected $fillable = ['invoice_number'];
            public $timestamps = false;

            protected static function booted()
            {
                static::creating(function ($model) {
                    $model->invoice_number = running_number()
                        ->type('invoice')
                        ->generate();
                });
            }
        };

        // Create multiple invoices
        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            $invoice = $invoiceModel->newInstance();
            $invoice->save();
            $numbers[] = $invoice->invoice_number;
        }

        // All numbers should be unique
        expect(count(array_unique($numbers)))->toBe(10)
            ->and($numbers[0])->toBe('INVOICE001')
            ->and($numbers[9])->toBe('INVOICE010');
    });
});

describe('Queue Job Integration', function () {
    it('generates unique numbers in queued jobs', function () {
        Queue::fake();

        $job = new class {
            public function handle()
            {
                return running_number()
                    ->type('order')
                    ->generate();
            }
        };

        // Simulate job execution
        $number = $job->handle();

        expect($number)->toBe('ORDER001');
    });

    it('handles concurrent queue jobs correctly', function () {
        $numbers = [];

        // Simulate multiple queue workers processing jobs
        for ($i = 0; $i < 5; $i++) {
            $job = new class {
                public function handle()
                {
                    return running_number()
                        ->type('ticket')
                        ->generate();
                }
            };

            $numbers[] = $job->handle();
        }

        // All numbers should be unique
        expect(count(array_unique($numbers)))->toBe(5)
            ->and($numbers)->toContain('TICKET001')
            ->and($numbers)->toContain('TICKET005');
    });
});

describe('API Request Integration', function () {
    it('generates running number in API endpoint', function () {
        // Simulate API controller method
        $controller = new class {
            public function store()
            {
                $orderNumber = running_number()
                    ->type('order')
                    ->generate();

                return response()->json([
                    'order_number' => $orderNumber,
                    'status' => 'created',
                ]);
            }
        };

        $response = $controller->store();
        $data = $response->getData(true);

        expect($data['order_number'])->toBe('ORDER001')
            ->and($data['status'])->toBe('created');
    });

    it('handles multiple concurrent API requests', function () {
        $controller = new class {
            public function store()
            {
                return running_number()
                    ->type('invoice')
                    ->generate();
            }
        };

        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $controller->store();
        }

        // All should be unique
        expect(count(array_unique($responses)))->toBe(10);
    });
});

describe('Database Transaction Integration', function () {
    it('rolls back running number on transaction failure', function () {
        try {
            \DB::transaction(function () {
                // Generate running number
                $number = running_number()
                    ->type('test')
                    ->generate();

                expect($number)->toBe('TEST001');

                // Force transaction to fail
                throw new \Exception('Simulated failure');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Number should have been rolled back
        $record = RunningNumber::where('type', 'TEST')->first();
        expect($record)->toBeNull();
    });

    it('commits running number on successful transaction', function () {
        \DB::transaction(function () {
            $number = running_number()
                ->type('test')
                ->generate();

            expect($number)->toBe('TEST001');
        });

        // Number should be committed
        $record = RunningNumber::where('type', 'TEST')->first();
        expect($record)->not->toBeNull()
            ->and($record->number)->toBe(1);
    });

    it('handles nested transactions correctly', function () {
        \DB::transaction(function () {
            running_number()->type('test')->generate(); // TEST001

            \DB::transaction(function () {
                running_number()->type('test')->generate(); // TEST002
            });

            running_number()->type('test')->generate(); // TEST003
        });

        $record = RunningNumber::where('type', 'TEST')->first();
        expect($record->number)->toBe(3);
    });
});

describe('Multi-tenancy Scenario', function () {
    it('generates separate sequences per tenant using scopes', function () {
        // Tenant 1
        $tenant1Numbers = [];
        for ($i = 0; $i < 3; $i++) {
            $tenant1Numbers[] = running_number()
                ->type('invoice')
                ->scope('tenant-1')
                ->generate();
        }

        // Tenant 2
        $tenant2Numbers = [];
        for ($i = 0; $i < 3; $i++) {
            $tenant2Numbers[] = running_number()
                ->type('invoice')
                ->scope('tenant-2')
                ->generate();
        }

        // Each tenant should have their own sequence
        expect($tenant1Numbers)->toBe(['INVOICE001', 'INVOICE002', 'INVOICE003'])
            ->and($tenant2Numbers)->toBe(['INVOICE001', 'INVOICE002', 'INVOICE003']);
    });

    it('maintains tenant isolation', function () {
        // Generate for tenant 1
        running_number()->type('order')->scope('tenant-1')->generate();
        running_number()->type('order')->scope('tenant-1')->generate();

        // Generate for tenant 2
        running_number()->type('order')->scope('tenant-2')->generate();

        // Check database records
        $tenant1Record = RunningNumber::where('type', 'ORDER')
            ->where('scope', 'tenant-1')
            ->first();

        $tenant2Record = RunningNumber::where('type', 'ORDER')
            ->where('scope', 'tenant-2')
            ->first();

        expect($tenant1Record->number)->toBe(2)
            ->and($tenant2Record->number)->toBe(1);
    });
});

describe('Real-world Scenario: Invoice Generation', function () {
    it('handles complete invoice workflow', function () {
        // Create invoices table
        Schema::create('invoices', function ($table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('customer_name');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        // Invoice model with running number
        $invoiceModel = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'invoices';
            protected $fillable = ['invoice_number', 'customer_name', 'amount', 'status'];

            protected static function booted()
            {
                static::creating(function ($model) {
                    $model->invoice_number = running_number()
                        ->type('invoice')
                        ->generate();
                });
            }
        };

        // Create invoices
        $invoice1 = $invoiceModel->newInstance();
        $invoice1->customer_name = 'John Doe';
        $invoice1->amount = 1000.00;
        $invoice1->save();

        $invoice2 = $invoiceModel->newInstance();
        $invoice2->customer_name = 'Jane Smith';
        $invoice2->amount = 2000.00;
        $invoice2->save();

        expect($invoice1->invoice_number)->toBe('INVOICE001')
            ->and($invoice2->invoice_number)->toBe('INVOICE002');

        // Verify running number sequence in database
        $runningNumberRecord = RunningNumber::where('type', 'INVOICE')->first();
        expect($runningNumberRecord)->not->toBeNull()
            ->and($runningNumberRecord->number)->toBe(2);

        // Verify all invoices have unique numbers
        $allInvoices = \DB::table('invoices')->pluck('invoice_number')->toArray();
        expect(count($allInvoices))->toBe(2)
            ->and(count(array_unique($allInvoices)))->toBe(2);
    });
});

describe('Performance Under Load', function () {
    it('handles high-volume generation efficiently', function () {
        $startTime = microtime(true);

        // Generate 100 numbers
        for ($i = 0; $i < 100; $i++) {
            running_number()->type('test')->generate();
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete in reasonable time (adjust threshold as needed)
        expect($duration)->toBeLessThan(5.0); // 5 seconds for 100 generations

        // Verify all were generated
        $record = RunningNumber::where('type', 'TEST')->first();
        expect($record->number)->toBe(100);
    });

    it('maintains performance with batch generation', function () {
        $startTime = microtime(true);

        // Generate 100 numbers in batch
        $numbers = running_number()->type('test')->generateBatch(100);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Batch should be faster than individual generation
        expect($duration)->toBeLessThan(2.0) // 2 seconds for batch
            ->and(count($numbers))->toBe(100);
    });
});

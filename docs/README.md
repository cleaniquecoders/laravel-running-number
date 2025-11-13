# Laravel Running Number Documentation

Welcome to the comprehensive documentation for Laravel Running Number - a production-ready package for generating sequential running numbers in your Laravel applications with thread-safety, auto-reset periods, and extensive customization options.

## 📚 Documentation Structure

### [01. Getting Started](01-getting-started/)

Everything you need to get up and running with the package.

- **[Installation](01-getting-started/01-installation.md)** - Install and set up the package in minutes
- **[Quick Start](01-getting-started/02-quick-start.md)** - Generate your first running number
- **[Core Concepts](01-getting-started/03-core-concepts.md)** - Understand how the package works

### [02. Configuration](02-configuration/)

Learn how to configure the package for your specific needs.

- **[Overview](02-configuration/01-overview.md)** - Configuration file structure and options
- **[Types](02-configuration/02-types.md)** - Defining and managing running number types
- **[Enums](02-configuration/03-enums.md)** - Working with PHP 8.1+ native enums
- **[Custom Models](02-configuration/04-custom-models.md)** - Extending the RunningNumber model

### [03. Usage](03-usage/)

Practical examples and common usage patterns.

- **[Helper Functions](03-usage/01-helper-functions.md)** - Using the global helper function
- **[Generator Class](03-usage/02-generator-class.md)** - Direct generator instantiation and usage
- **[Facade](03-usage/03-facade.md)** - Using the RunningNumber facade
- **[Model Integration](03-usage/04-model-integration.md)** - Integrating with Eloquent models using the trait
- **[Common Scenarios](03-usage/05-common-scenarios.md)** - Real-world implementation examples
- **[Artisan Commands](03-usage/06-artisan-commands.md)** - Managing running numbers via CLI
- **[Events](03-usage/07-events.md)** - Listening to generation events for auditing and logging
- **[REST API](03-usage/08-rest-api.md)** - HTTP endpoints for remote number generation

### [04. Features](04-features/)

Advanced features and capabilities.

- **[Date-Based Formats](04-features/01-date-based-formats.md)** - Date prefixes and time-based numbering
- **[Multiple Sequences](04-features/02-multiple-sequences.md)** - Scoped sequences per type
- **[Custom Starting Numbers](04-features/03-custom-starting-numbers.md)** - Define custom start points
- **[Number Range Management](04-features/04-number-range-management.md)** - Maximum number limits
- **[Preview and Batch](04-features/05-preview-and-batch.md)** - Preview and bulk generation

### [05. Advanced](05-advanced/)

Advanced customization and extension points.

- **[Custom Presenters](05-advanced/01-custom-presenters.md)** - Create custom formatting logic
- **[Custom Generators](05-advanced/02-custom-generators.md)** - Extend generation behavior
- **[Integration Patterns](05-advanced/03-integration-patterns.md)** - Advanced integration strategies

### [06. Development](06-development/)

Resources for contributors and package developers.

- **[Testing](06-development/01-testing.md)** - Testing strategies and examples
- **[Contributing](06-development/02-contributing.md)** - How to contribute to the project
- **[Development Setup](06-development/03-development-setup.md)** - Local development environment

### [07. Upgrade Guide](07-upgrade/)

Complete upgrade guide for all versions.

- **[Upgrade Guide](07-upgrade/01-upgrade-guide.md)** - Complete upgrade guide for all versions
  - **v3.0.0** - Code quality, testing improvements, service container integration
  - **v2.x** - Native PHP enums, UUID support
  - **Version History** - Complete changelog

## 🚀 Quick Navigation

### New Users

1. Start with [Installation](01-getting-started/01-installation.md)
2. Follow the [Quick Start](01-getting-started/02-quick-start.md) guide
3. Explore [Common Scenarios](03-usage/05-common-scenarios.md)

### Experienced Users

- [Configuration Overview](02-configuration/01-overview.md) - Customize the package
- [Advanced Features](04-features/) - Date formats, scopes, reset periods
- [Custom Presenters](05-advanced/01-custom-presenters.md) - Create custom formats
- [Model Integration](03-usage/04-model-integration.md) - Advanced model patterns

### Contributors

- [Contributing Guidelines](06-development/02-contributing.md)
- [Development Setup](06-development/03-development-setup.md)
- [Testing Guide](06-development/01-testing.md)

## 📖 Key Features

- **Sequential Generation**: Automatic sequential number generation per type
- **Database Persistence**: Reliable state storage with Eloquent ORM
- **Thread-Safe Operations**: Race condition protection with database transactions and row-level locking
- **Auto-Reset Periods**: Configurable reset cycles (never, daily, monthly, yearly)
- **Multiple Sequences**: Independent sequences per type using scopes
- **UUID Support**: Built-in UUID generation with Traitify package
- **PHP 8.1+ Enums**: Native enum support for type-safe configurations
- **Date-Based Formats**: Multiple date presenters for time-based numbering
- **Number Range Management**: Define min/max limits with validation
- **Preview Mode**: Preview next numbers without incrementing counters
- **Bulk Generation**: Generate multiple numbers atomically
- **Eloquent Trait**: Auto-generate numbers on model creation with InteractsWithRunningNumber
- **Artisan Commands**: CLI commands for managing, listing, resetting, and creating running numbers
- **Event System**: Built-in events for auditing, logging, and notifications
- **REST API**: Optional HTTP endpoints for remote number generation and management
- **Highly Configurable**: Customize padding, formatting, and behavior
- **Extensible Architecture**: Custom generators and presenters via contracts
- **Developer Friendly**: Helper functions, facades, trait, commands, events, API, and excellent IDE support
- **Comprehensively Tested**: 143 tests, 413 assertions, PHPStan Level 5, 100% type coverage
- **Laravel 9-12**: Full support for Laravel 9.x, 10.x, 11.x, and 12.x
- **PHP 8.1-8.4**: Supports PHP 8.1, 8.2, 8.3, and 8.4
- **Production Ready**: Battle-tested for high-concurrency enterprise applications

## 🔍 Quick Examples

### Basic Usage

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

// Simple generation
$number = running_number()
    ->type(Organization::INVOICE->value)
    ->generate();
// Output: INVOICE001

// With custom starting number
$number = running_number()
    ->type('ticket')
    ->startFrom(1000)
    ->generate();
// Output: TICKET1001

// With scope for multi-tenant or departmental sequences
$number = running_number()
    ->type('order')
    ->scope('retail')
    ->generate();
// Output: ORDER001 (independent from other scopes)
```

### Model Integration

```php
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected static function booted()
    {
        static::creating(function ($invoice) {
            // Auto-generate invoice number on creation
            $invoice->invoice_number = running_number()
                ->type('invoice')
                ->scope($invoice->tenant_id) // Multi-tenant support
                ->generate();
        });
    }
}

// Usage
$invoice = Invoice::create([
    'tenant_id' => 'acme-corp',
    'amount' => 1500.00,
]);
// invoice_number auto-set: INVOICE001
```

### Date-Based Formats

```php
use CleaniqueCoders\RunningNumber\Presenters\DatePrefixPresenter;
use CleaniqueCoders\RunningNumber\Presenters\YearMonthPresenter;

// Date prefix format: INVOICE-2025-11-13-001
$number = running_number()
    ->type('invoice')
    ->formatter(new DatePrefixPresenter())
    ->generate();

// Year-month format: ORDER-2025-11-001
$number = running_number()
    ->type('order')
    ->formatter(new YearMonthPresenter())
    ->generate();
```

### Preview and Batch Generation

```php
// Preview next number without incrementing
$preview = running_number()
    ->type('ticket')
    ->preview();
// Output: TICKET010 (no database change)

// Generate multiple numbers atomically
$numbers = running_number()
    ->type('voucher')
    ->generateBatch(10);
// Output: ['VOUCHER001', 'VOUCHER002', ..., 'VOUCHER010']
```

### Using the Eloquent Trait

```php
use CleaniqueCoders\RunningNumber\Concerns\InteractsWithRunningNumber;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use InteractsWithRunningNumber;

    protected $fillable = ['invoice_number', 'customer_id', 'amount'];

    // Configure automatic generation
    protected string $runningNumberField = 'invoice_number';
    protected string $runningNumberType = 'invoice';
    protected ?string $runningNumberScope = '$customer_id'; // Dynamic scope
}

// Numbers auto-generate on creation
$invoice = Invoice::create([
    'customer_id' => 1,
    'amount' => 1500.00,
]);
// invoice_number auto-set: INVOICE001
```

### Using Artisan Commands

```bash
# List all running numbers
php artisan running-number:list

# Create a new type
php artisan running-number:create invoice --start=1000 --reset=monthly

# Reset a type to zero
php artisan running-number:reset invoice --force
```

### Listening to Events

```php
use CleaniqueCoders\RunningNumber\Events\RunningNumberGenerated;
use Illuminate\Support\Facades\Event;

Event::listen(RunningNumberGenerated::class, function ($event) {
    // Log all generated numbers for auditing
    logger()->info("Generated: {$event->formattedNumber}", [
        'type' => $event->type,
        'scope' => $event->scope,
        'uuid' => $event->model->uuid,
    ]);
});
```

### Service Container Integration

```php
use CleaniqueCoders\RunningNumber\Contracts\Generator;

class OrderService
{
    public function __construct(
        private Generator $generator
    ) {}

    public function createOrder(array $data): Order
    {
        $orderNumber = $this->generator
            ->type('order')
            ->scope($data['department'])
            ->generate();

        return Order::create([
            'order_number' => $orderNumber,
            ...$data
        ]);
    }
}

// Laravel automatically injects the Generator
$service = app(OrderService::class);
```

### Thread-Safe Concurrent Generation

```php
use Illuminate\Support\Facades\DB;

// The package handles race conditions automatically
DB::transaction(function () {
    // Even with concurrent requests, numbers are never duplicated
    $number = running_number()
        ->type('invoice')
        ->generate();

    Invoice::create([
        'invoice_number' => $number,
        'amount' => 1000.00,
    ]);
});
// Uses row-level locking and atomic operations internally
```

### Using REST API

```bash
# Generate a number via HTTP
curl -X POST http://localhost:8000/api/running-numbers/generate \
  -H "Content-Type: application/json" \
  -d '{"type": "invoice", "scope": "retail"}'

# Response:
# {
#   "success": true,
#   "data": {
#     "type": "INVOICE",
#     "number": "INVOICE001",
#     "scope": "retail"
#   }
# }

# Check current number
curl http://localhost:8000/api/running-numbers/current/invoice

# Preview next number
curl http://localhost:8000/api/running-numbers/preview/invoice
```

## 📊 Package Statistics

- **Total Tests**: 143 tests with 413 assertions
- **Code Quality**: PHPStan Level 5 with 0 errors
- **Type Coverage**: 100% type hints on all methods
- **Test Coverage**: Comprehensive coverage including edge cases, concurrency, and API tests
- **Laravel Support**: 9.x, 10.x, 11.x, 12.x
- **PHP Support**: 8.1, 8.2, 8.3, 8.4
- **Documentation**: 40+ pages of comprehensive guides with real-world examples
- **New Features**: Eloquent trait, 3 Artisan commands, Event system, REST API

## 🆘 Getting Help

- **Documentation**: You're reading it!
- **GitHub Issues**: [Report bugs or request features](https://github.com/cleaniquecoders/laravel-running-number/issues)
- **GitHub Discussions**: [Ask questions and share ideas](https://github.com/cleaniquecoders/laravel-running-number/discussions)

## 📝 Additional Resources

- [CHANGELOG](../CHANGELOG.md) - Version history and detailed changes
- [Upgrade Guide](07-upgrade/01-upgrade-guide.md) - Complete upgrade guide for all versions
- [LICENSE](../LICENSE.md) - MIT License

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](06-development/02-contributing.md) for details on:

- Code standards and quality requirements
- Testing requirements (PHPStan Level 5, 100% type coverage)
- Pull request process
- Development setup

## 📄 License

Laravel Running Number is open-sourced software licensed under the [MIT license](../LICENSE.md).

---

**Ready to get started?** Head to the [Installation Guide](01-getting-started/01-installation.md) or explore [Common Scenarios](03-usage/05-common-scenarios.md) for real-world examples!

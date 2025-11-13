# Configuration Overview

This guide provides a detailed explanation of each configuration option in `config/running-number.php`.

## Configuration File Structure

```php
<?php

use CleaniqueCoders\RunningNumber\Enums\Organization;
use CleaniqueCoders\RunningNumber\Enums\ResetPeriod;

return [
    'types' => Organization::values(),
    'model' => \CleaniqueCoders\RunningNumber\Models\RunningNumber::class,
    'generator' => \CleaniqueCoders\RunningNumber\Generator::class,
    'presenter' => \CleaniqueCoders\RunningNumber\Presenter::class,
    'padding' => 3,
    'reset_period' => [
        'default' => ResetPeriod::NEVER->value,
        'types' => [
            // Per-type reset periods (optional)
        ],
    ],
];
```

## Configuration Options

### Types

**Key**: `types`
**Type**: `array`
**Default**: `Organization::values()`

Defines the allowed running number types for your application. Only types listed here can be used to generate running numbers.

```php
'types' => [
    'invoice',
    'order',
    'receipt',
    'ticket',
],
```

**Using Enums (Recommended):**

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

'types' => Organization::values(),
// Returns: ['organization', 'division', 'section', 'unit', 'profile']
```

**Validation:**

If you attempt to generate a running number with an undefined type, an `InvalidRunningNumberTypeException` will be thrown:

```php
// This will throw an exception if 'invalid_type' is not in the config
running_number()->type('invalid_type')->generate();
// InvalidRunningNumberTypeException: Unsupported invalid_type
```

**Best Practices:**

- Use native PHP enums with `values()` method for type safety
- Use lowercase for type values (they're converted to uppercase by default)
- Keep type names descriptive and consistent
- Document custom types in your application

### Model

**Key**: `model`
**Type**: `string`
**Default**: `\CleaniqueCoders\RunningNumber\Models\RunningNumber::class`

Specifies the Eloquent model class used to interact with the running numbers table.

```php
'model' => \CleaniqueCoders\RunningNumber\Models\RunningNumber::class,
```

**Custom Model Example:**

```php
'model' => \App\Models\CustomRunningNumber::class,
```

Your custom model should extend the base model or implement the same structure:

```php
namespace App\Models;

use CleaniqueCoders\RunningNumber\Models\RunningNumber as BaseModel;

class CustomRunningNumber extends BaseModel
{
    // Add custom methods or override behavior

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

See [Custom Models](04-custom-models.md) for more details.

### Generator

**Key**: `generator`
**Type**: `string`
**Default**: `\CleaniqueCoders\RunningNumber\Generator::class`

Specifies the generator class responsible for creating running numbers.

```php
'generator' => \CleaniqueCoders\RunningNumber\Generator::class,
```

**Custom Generator Example:**

```php
'generator' => \App\Services\CustomGenerator::class,
```

Your custom generator must implement the `GeneratorContract`:

```php
namespace App\Services;

use CleaniqueCoders\RunningNumber\Contracts\Generator as GeneratorContract;
use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class CustomGenerator implements GeneratorContract
{
    public static function make(): GeneratorContract
    {
        return new self();
    }

    public function type($type)
    {
        // Implementation
    }

    public function toUpperCase($value)
    {
        // Implementation
    }

    public function formatter(Presenter $presenter): GeneratorContract
    {
        // Implementation
    }

    public function generate(): string
    {
        // Custom generation logic
    }
}
```

See [Custom Generators](../04-advanced/02-custom-generators.md) for more details.

### Presenter

**Key**: `presenter`
**Type**: `string`
**Default**: `\CleaniqueCoders\RunningNumber\Presenter::class`

Specifies the presenter class responsible for formatting the output.

```php
'presenter' => \CleaniqueCoders\RunningNumber\Presenter::class,
```

The default presenter creates this format:

```php
TYPE001, TYPE002, TYPE003, ...
```

**Custom Presenter Example:**

```php
'presenter' => \App\Services\CustomPresenter::class,
```

Your custom presenter must implement the `PresenterContract`:

```php
namespace App\Services;

use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class CustomPresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        // Custom format: TYPE-YYYY-00001
        return sprintf(
            '%s-%s-%s',
            $type,
            date('Y'),
            str_pad($number, 5, '0', STR_PAD_LEFT)
        );
    }
}
```

This would generate: `INVOICE-2025-00001`, `INVOICE-2025-00002`, etc.

See [Custom Presenters](../04-advanced/01-custom-presenters.md) for more examples.

### Padding

**Key**: `padding`
**Type**: `integer`
**Default**: `3`

Determines how many digits the numeric portion should have. The number is left-padded with zeros.

```php
'padding' => 3,
```

**Examples:**

```php
// Padding: 3
INVOICE001, INVOICE002, ..., INVOICE999, INVOICE1000

// Padding: 5
INVOICE00001, INVOICE00002, ..., INVOICE99999, INVOICE100000

// Padding: 2
INVOICE01, INVOICE02, ..., INVOICE99, INVOICE100
```

**Note**: The number automatically expands beyond the padding when needed. It never wraps around unless you configure reset periods.

### Reset Period

**Key**: `reset_period`
**Type**: `array`
**Default**: `['default' => 'never']`

Controls when running numbers should automatically reset to 1. This is useful for scenarios where you want numbers to restart periodically (e.g., yearly invoices, monthly receipts).

```php
'reset_period' => [
    'default' => 'never',  // Global default for all types

    // Per-type configuration (optional)
    'types' => [
        'invoice' => 'yearly',
        'receipt' => 'monthly',
        'ticket' => 'daily',
    ],
],
```

**Available Periods:**

- `never` - Numbers never reset (continuous sequence)
- `daily` - Reset at midnight every day
- `monthly` - Reset on the 1st of each month
- `yearly` - Reset on January 1st each year

**Using Enum (Recommended):**

```php
use CleaniqueCoders\RunningNumber\Enums\ResetPeriod;

'reset_period' => [
    'default' => ResetPeriod::NEVER->value,
    'types' => [
        'invoice' => ResetPeriod::YEARLY->value,
        'receipt' => ResetPeriod::MONTHLY->value,
    ],
],
```

**How It Works:**

```php
// January 2024
running_number()->type('invoice')->generate(); // INVOICE001
running_number()->type('invoice')->generate(); // INVOICE002

// ... time passes to January 2025 ...

// Automatically resets!
running_number()->type('invoice')->generate(); // INVOICE001
```

**Real-World Scenarios:**

```php
// Yearly invoices: INV-2024-001, INV-2024-002, ..., INV-2025-001
'invoice' => 'yearly',

// Monthly receipts: RCP-JAN-001, RCP-JAN-002, ..., RCP-FEB-001
'receipt' => 'monthly',

// Daily tickets: TKT-001, TKT-002 (resets every midnight)
'ticket' => 'daily',

// Continuous orders: ORD-001, ORD-002, ... (never resets)
'order' => 'never',
```

**Manual Reset:**

You can also manually reset numbers programmatically:

```php
use CleaniqueCoders\RunningNumber\Models\RunningNumber;

$record = RunningNumber::where('type', 'INVOICE')->first();
$record->reset(); // Resets number to 0 and updates last_reset_at
```

**Best Practices:**

- Use yearly resets for financial documents (invoices, quotes)
- Use monthly resets for recurring reports
- Use never for continuous sequences (customer IDs, order numbers)
- Document reset behavior in your application for users

## Environment-Based Configuration

You can make configuration values environment-specific:

```php
'types' => env('RUNNING_NUMBER_TYPES', 'invoice,order,receipt'),
'padding' => env('RUNNING_NUMBER_PADDING', 3),
```

In your `.env` file:

```env
RUNNING_NUMBER_TYPES=invoice,order,receipt,ticket
RUNNING_NUMBER_PADDING=5
```

**Processing array from env:**

```php
'types' => explode(',', env('RUNNING_NUMBER_TYPES', 'invoice,order')),
```

## Configuration Caching

In production, cache your configuration for better performance:

```bash
php artisan config:cache
```

After changing configuration:

```bash
php artisan config:clear
php artisan config:cache
```

## Verifying Configuration

Check your current configuration in `php artisan tinker`:

```php
config('running-number.types');
config('running-number.padding');
config('running-number.model');
```

## Common Configuration Patterns

### Document-Based Types

```php
'types' => [
    'invoice',
    'quote',
    'receipt',
    'credit_note',
    'delivery_note',
],
```

### Asset Management

```php
'types' => [
    'asset',
    'equipment',
    'vehicle',
    'property',
],
```

### HR System

```php
'types' => [
    'employee',
    'applicant',
    'contractor',
    'department',
],
```

### Mixed Approach

```php
use App\Enums\DocumentType;
use App\Enums\AssetType;

'types' => array_merge(
    DocumentType::values(),
    AssetType::values(),
    ['custom_type']
),
```

## Configuration Validation

Create a custom configuration validator:

```php
// In AppServiceProvider::boot()
$types = config('running-number.types');

if (empty($types)) {
    throw new \RuntimeException('Running number types cannot be empty');
}

if (!is_array($types)) {
    throw new \RuntimeException('Running number types must be an array');
}
```

## Next Steps

- **[Types](02-types.md)** - Learn about defining custom types
- **[Enums](03-enums.md)** - Create type-safe enums
- **[Custom Models](04-custom-models.md)** - Extend the running number model
- **[Custom Presenters](../04-advanced/01-custom-presenters.md)** - Create custom formatters

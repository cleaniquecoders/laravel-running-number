# Usage

Learn how to use Laravel Running Number in your Laravel application with practical examples.

## Table of Contents

1. [Helper Functions](01-helper-functions.md) - Using the `running_number()` helper
2. [Generator Class](02-generator-class.md) - Direct usage of the Generator class
3. [Facade](03-facade.md) - Using the RunningNumber facade
4. [Model Integration](04-model-integration.md) - Integrating with Eloquent models (includes InteractsWithRunningNumber trait)
5. [Common Scenarios](05-common-scenarios.md) - Real-world usage examples
6. [Artisan Commands](06-artisan-commands.md) - Managing running numbers via CLI
7. [Events](07-events.md) - Listening to running number generation events

## Quick Examples

### Basic Generation

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

$number = running_number()
    ->type(Organization::PROFILE->value)
    ->generate();
```

### With Custom Formatting

```php
$number = running_number()
    ->type('invoice')
    ->toUpperCase(false)
    ->generate();
```

### In Model Events

```php
class Invoice extends Model
{
    protected static function booted()
    {
        static::creating(function ($invoice) {
            $invoice->invoice_number = running_number()
                ->type('invoice')
                ->generate();
        });
    }
}
```

## Next Steps

Explore each section to learn more about using Laravel Running Number effectively in your application.

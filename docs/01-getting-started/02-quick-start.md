# Quick Start

Get up and running with Laravel Running Number in just a few minutes! This guide covers the most common use cases.

## Basic Usage

### Using the Helper Function

The simplest way to generate a running number is using the `running_number()` helper function:

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

$runningNumber = running_number()
    ->type(Organization::PROFILE->value)
    ->generate();

echo $runningNumber; // Output: PROFILE001
```

### Using the Generator Class

You can also use the Generator class directly:

```php
use CleaniqueCoders\RunningNumber\Generator;
use CleaniqueCoders\RunningNumber\Enums\Organization;

$runningNumber = Generator::make()
    ->type(Organization::ORGANIZATION->value)
    ->generate();

echo $runningNumber; // Output: ORGANIZATION001
```

### Using the Facade

For those who prefer facades:

```php
use CleaniqueCoders\RunningNumber\Facades\RunningNumber;
use CleaniqueCoders\RunningNumber\Enums\Organization;

$runningNumber = RunningNumber::type(Organization::DIVISION->value)->generate();

echo $runningNumber; // Output: DIVISION001
```

## Common Scenarios

### Invoice Numbers

Generate sequential invoice numbers for your application:

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

$invoiceNumber = running_number()
    ->type('invoice')
    ->generate();

// Output: INVOICE001, INVOICE002, INVOICE003, ...
```

**Note**: Make sure to add `'invoice'` to your `config/running-number.php` types array.

### Order Numbers

Create sequential order numbers:

```php
$orderNumber = running_number()
    ->type('order')
    ->generate();

// Output: ORDER001, ORDER002, ORDER003, ...
```

### Receipt Numbers

Generate receipt numbers with lowercase formatting:

```php
$receiptNumber = running_number()
    ->type('receipt')
    ->toUpperCase(false)
    ->generate();

// Output: receipt001, receipt002, receipt003, ...
```

## Working with Models

Integrate running numbers directly into your Eloquent models:

```php
use Illuminate\Database\Eloquent\Model;
use CleaniqueCoders\RunningNumber\Enums\Organization;

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

Now every time you create a new invoice, it automatically gets a running number:

```php
$invoice = Invoice::create([
    'customer_id' => 1,
    'amount' => 100.00,
]);

echo $invoice->invoice_number; // INVOICE001
```

## Case Sensitivity

By default, running numbers are generated in UPPERCASE. You can change this behavior:

```php
// Uppercase (default)
$number = running_number()
    ->type(Organization::PROFILE->value)
    ->generate();
// Output: PROFILE001

// Lowercase
$number = running_number()
    ->type(Organization::PROFILE->value)
    ->toUpperCase(false)
    ->generate();
// Output: profile002
```

## Multiple Types

Generate running numbers for different types independently:

```php
// Each type maintains its own sequence
$invoice = running_number()->type('invoice')->generate();   // INVOICE001
$order = running_number()->type('order')->generate();       // ORDER001
$invoice2 = running_number()->type('invoice')->generate();  // INVOICE002
$order2 = running_number()->type('order')->generate();      // ORDER002
```

## Viewing Generated Numbers

You can query the running numbers table directly to see all generated sequences:

```php
use CleaniqueCoders\RunningNumber\Models\RunningNumber;

// Get all running number types
$types = RunningNumber::all();

// Get specific type
$profileNumbers = RunningNumber::where('type', 'PROFILE')->first();
echo $profileNumbers->number; // Current count

// Access UUID
echo $profileNumbers->uuid; // e.g., "550e8400-e29b-41d4-a716-446655440000"
```

## What's Next?

Now that you've learned the basics, explore these topics:

- **[Core Concepts](03-core-concepts.md)** - Understand how the package works under the hood
- **[Configuration](../02-configuration/01-overview.md)** - Customize the package to your needs
- **[Usage Guide](../03-usage/01-helper-functions.md)** - Learn advanced usage patterns
- **[Advanced Topics](../04-advanced/01-custom-presenters.md)** - Create custom formatters and extend functionality

## Quick Reference

```php
// Basic generation
running_number()->type('invoice')->generate();

// Lowercase
running_number()->type('receipt')->toUpperCase(false)->generate();

// Using enum
use CleaniqueCoders\RunningNumber\Enums\Organization;
running_number()->type(Organization::PROFILE->value)->generate();

// In model events
static::creating(function ($model) {
    $model->number = running_number()->type('type')->generate();
});
```

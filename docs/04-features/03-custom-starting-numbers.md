# Custom Starting Numbers

Configure running number sequences to start from a specific number instead of the default zero. This is useful for migrating from existing systems or following specific numbering conventions.

## Basic Usage

```php
use CleaniqueCoders\RunningNumber\Generator;

$number = Generator::make()
    ->type('invoice')
    ->startFrom(1000)
    ->generate();

// First generation: INVOICE1001
// Second generation: INVOICE1002
// Third generation: INVOICE1003
```

## How It Works

The `startFrom()` method sets the initial value when creating a new running number type:

1. **New Type**: The sequence starts from the specified number
2. **Existing Type**: The `startFrom()` value is ignored, sequence continues

```php
// First call - creates new type starting at 1000
Generator::make()
    ->type('order')
    ->startFrom(1000)
    ->generate();
// Output: ORDER1001

// Second call - startFrom is ignored, continues from 1001
Generator::make()
    ->type('order')
    ->startFrom(5000)  // Ignored!
    ->generate();
// Output: ORDER1002
```

## Common Use Cases

### System Migration

When migrating from an old system:

```php
// Start invoices from your last invoice number
$number = Generator::make()
    ->type('invoice')
    ->startFrom(45231)
    ->generate();
// Output: INVOICE45232
```

### Sequential Year-Based Numbering

```php
$year = date('Y');
$startNumber = $year * 10000;

$number = Generator::make()
    ->type('invoice')
    ->startFrom($startNumber)
    ->generate();
// 2025: INVOICE20250001
```

### Department-Specific Ranges

```php
// Sales: 1000-1999
$sales = Generator::make()
    ->type('order')
    ->scope('sales')
    ->startFrom(1000)
    ->generate();

// Marketing: 2000-2999
$marketing = Generator::make()
    ->type('order')
    ->scope('marketing')
    ->startFrom(2000)
    ->generate();
```

### VIP Customer Sequences

```php
// VIP customers get numbers starting from 9000
$vipNumber = Generator::make()
    ->type('customer')
    ->scope('vip')
    ->startFrom(9000)
    ->generate();
// Output: CUSTOMER9001
```

## Combining with Other Features

### With Scopes

Each scope can have its own starting number:

```php
$retail = Generator::make()
    ->type('invoice')
    ->scope('retail')
    ->startFrom(1000)
    ->generate();
// Output: INVOICE1001

$wholesale = Generator::make()
    ->type('invoice')
    ->scope('wholesale')
    ->startFrom(5000)
    ->generate();
// Output: INVOICE5001
```

### With Max Numbers

Define a specific range:

```php
$number = Generator::make()
    ->type('ticket')
    ->startFrom(100)
    ->maxNumber(200)
    ->generate();
// Generates numbers from 101 to 200
```

### With Date-Based Formats

```php
use CleaniqueCoders\RunningNumber\Presenters\YearMonthPresenter;

$invoice = Generator::make()
    ->type('invoice')
    ->startFrom(1000)
    ->formatter(new YearMonthPresenter())
    ->generate();
// Output: INVOICE-2025-11-1001
```

### With Reset Periods

Starting number is used when creating or resetting:

```php
// config/running-number.php
'reset_period' => [
    'types' => [
        'invoice' => 'yearly',
    ],
],

// Each year, sequence resets to starting number
$invoice = Generator::make()
    ->type('invoice')
    ->startFrom(1000)
    ->generate();
// 2025: Starts at INVOICE1001
// 2026: Resets to INVOICE1001 (after yearly reset)
```

## Special Cases

### Starting from Zero

Starting from zero is the default behavior:

```php
$number = Generator::make()
    ->type('invoice')
    ->startFrom(0)
    ->generate();
// Output: INVOICE001
```

### Large Starting Numbers

```php
$number = Generator::make()
    ->type('tracking')
    ->startFrom(999999)
    ->generate();
// Output: TRACKING1000000
```

### Negative Starting Numbers

While supported, use with caution:

```php
$number = Generator::make()
    ->type('adjustment')
    ->startFrom(-10)
    ->generate();
// Output: ADJUSTMENT0-9 (padding behavior may vary)
```

## Database Behavior

The starting number is stored in the database when the type is first created:

```php
// First generation creates database record
Generator::make()
    ->type('invoice')
    ->startFrom(1000)
    ->generate();
```

Database record:
| type    | scope | number |
|---------|-------|--------|
| INVOICE | null  | 1001   |

## Model Integration

```php
class Invoice extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $startingNumber = config('invoice.starting_number', 0);

            $invoice->invoice_number = Generator::make()
                ->type('invoice')
                ->startFrom($startingNumber)
                ->generate();
        });
    }
}
```

## Configuration-Based Starting Numbers

You can configure starting numbers in your configuration:

```php
// config/running-number.php
return [
    'starting_numbers' => [
        'invoice' => 1000,
        'receipt' => 5000,
        'order' => 10000,
    ],
];

// Usage
$startingNumber = config('running-number.starting_numbers.invoice', 0);

$number = Generator::make()
    ->type('invoice')
    ->startFrom($startingNumber)
    ->generate();
```

## Best Practices

1. **Set Once**: Starting numbers only affect the initial creation
2. **Document Ranges**: Document your numbering ranges for different types/scopes
3. **Consider Padding**: Ensure padding accommodates your starting numbers
4. **Migration Planning**: Plan starting numbers when migrating from existing systems
5. **Scope Isolation**: Use scopes with different starting numbers for range separation
6. **Validate Ranges**: When using max numbers, ensure starting number is less than max

## Examples

### E-commerce Platform

```php
// Orders: Start from 10000
$order = Generator::make()
    ->type('order')
    ->startFrom(10000)
    ->generate();

// Returns: Start from 20000
$return = Generator::make()
    ->type('return')
    ->startFrom(20000)
    ->generate();

// Refunds: Start from 30000
$refund = Generator::make()
    ->type('refund')
    ->startFrom(30000)
    ->generate();
```

### Multi-Location Business

```php
foreach ($locations as $location) {
    $startNumber = $location->id * 1000;

    $invoice = Generator::make()
        ->type('invoice')
        ->scope("location-{$location->id}")
        ->startFrom($startNumber)
        ->generate();
}

// Location 1: INVOICE1001, INVOICE1002, ...
// Location 2: INVOICE2001, INVOICE2002, ...
// Location 3: INVOICE3001, INVOICE3002, ...
```

### Year-Based Sequential

```php
$year = date('y'); // 25 for 2025
$startNumber = $year * 1000000;

$invoice = Generator::make()
    ->type('invoice')
    ->startFrom($startNumber)
    ->generate();
// 2025: INVOICE25000001
// 2026: INVOICE26000001 (if reset yearly)
```

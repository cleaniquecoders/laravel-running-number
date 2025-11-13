# Multiple Sequences Per Type

Use scopes to maintain multiple independent sequences for the same running number type. This is useful when you need separate numbering for different departments, branches, or categories.

## Basic Usage

```php
use CleaniqueCoders\RunningNumber\Generator;

// Retail sequence
$retail1 = Generator::make()
    ->type('invoice')
    ->scope('retail')
    ->generate();
// Output: INVOICE001

$retail2 = Generator::make()
    ->type('invoice')
    ->scope('retail')
    ->generate();
// Output: INVOICE002

// Wholesale sequence (independent)
$wholesale1 = Generator::make()
    ->type('invoice')
    ->scope('wholesale')
    ->generate();
// Output: INVOICE001

$wholesale2 = Generator::make()
    ->type('invoice')
    ->scope('wholesale')
    ->generate();
// Output: INVOICE002
```

## Database Structure

Each type+scope combination creates a separate database record:

| type    | scope     | number |
|---------|-----------|--------|
| INVOICE | retail    | 2      |
| INVOICE | wholesale | 2      |

The unique constraint on `[type, scope]` ensures data integrity.

## Scope vs No Scope

Running numbers without a scope are treated separately from scoped sequences:

```php
// No scope (null)
$noScope = Generator::make()
    ->type('invoice')
    ->generate();
// Output: INVOICE001

// With scope
$withScope = Generator::make()
    ->type('invoice')
    ->scope('online')
    ->generate();
// Output: INVOICE001 (different sequence)
```

Database records:

| type    | scope  | number |
|---------|--------|--------|
| INVOICE | null   | 1      |
| INVOICE | online | 1      |

## Common Use Cases

### Multi-Branch System

```php
// Branch A invoices
$branchA = Generator::make()
    ->type('invoice')
    ->scope('branch-a')
    ->generate();

// Branch B invoices
$branchB = Generator::make()
    ->type('invoice')
    ->scope('branch-b')
    ->generate();
```

### Department-Specific Numbering

```php
// Sales department
$sales = Generator::make()
    ->type('order')
    ->scope('sales')
    ->generate();

// Marketing department
$marketing = Generator::make()
    ->type('order')
    ->scope('marketing')
    ->generate();
```

### Category-Based Sequences

```php
// Different product categories
$electronics = Generator::make()
    ->type('product')
    ->scope('electronics')
    ->generate();

$clothing = Generator::make()
    ->type('product')
    ->scope('clothing')
    ->generate();
```

### Multi-Tenant Applications

```php
// Tenant isolation
$tenant1Invoice = Generator::make()
    ->type('invoice')
    ->scope("tenant_{$tenantId}")
    ->generate();
```

## Combining with Other Features

### Scopes with Custom Starting Numbers

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

### Scopes with Reset Periods

Each scope maintains its own reset schedule:

```php
// config/running-number.php
'reset_period' => [
    'types' => [
        'invoice' => 'monthly',
    ],
],

// Both scopes reset independently each month
$retail = Generator::make()
    ->type('invoice')
    ->scope('retail')
    ->generate();

$wholesale = Generator::make()
    ->type('invoice')
    ->scope('wholesale')
    ->generate();
```

### Scopes with Max Numbers

```php
$retail = Generator::make()
    ->type('invoice')
    ->scope('retail')
    ->maxNumber(9999)
    ->generate();

$wholesale = Generator::make()
    ->type('invoice')
    ->scope('wholesale')
    ->maxNumber(99999)
    ->generate();
```

### Scopes with Date-Based Formats

```php
use CleaniqueCoders\RunningNumber\Presenters\YearMonthPresenter;

$retail = Generator::make()
    ->type('invoice')
    ->scope('retail')
    ->formatter(new YearMonthPresenter())
    ->generate();
// Output: INVOICE-2025-11-001

$wholesale = Generator::make()
    ->type('invoice')
    ->scope('wholesale')
    ->formatter(new YearMonthPresenter())
    ->generate();
// Output: INVOICE-2025-11-001 (separate sequence)
```

## Scope Naming Conventions

Scopes support various naming patterns:

```php
// Dash-separated
->scope('branch-01')

// Underscore-separated
->scope('branch_01')

// Mixed case
->scope('BranchA')

// Dot notation
->scope('dept.sales')
```

## Model Integration

When using scopes with models:

```php
class Invoice extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $invoice->invoice_number = Generator::make()
                ->type('invoice')
                ->scope($invoice->branch_code)
                ->generate();
        });
    }
}
```

## Dynamic Scopes

```php
public function generateInvoiceNumber($branchId)
{
    return Generator::make()
        ->type('invoice')
        ->scope("branch-{$branchId}")
        ->generate();
}
```

## Migration

The scope feature requires the `add_scope_to_running_numbers_table` migration:

```bash
php artisan migrate
```

This adds:
- `scope` column (nullable string)
- Composite unique constraint on `[type, scope]`

## Querying Scoped Numbers

```php
use CleaniqueCoders\RunningNumber\Models\RunningNumber;

// Get all scopes for a type
$invoiceScopes = RunningNumber::where('type', 'INVOICE')
    ->get(['scope', 'number']);

// Get specific scope
$retailNumber = RunningNumber::where('type', 'INVOICE')
    ->where('scope', 'retail')
    ->first();
```

## Best Practices

1. **Consistent Scope Naming**: Use a consistent naming convention across your application
2. **Document Scope Purpose**: Clearly document what each scope represents
3. **Validate Scopes**: Validate scope values before generating numbers
4. **Consider Data Growth**: Each scope creates a database record
5. **Use with Reset Periods**: Combine with reset periods for time-based scope isolation

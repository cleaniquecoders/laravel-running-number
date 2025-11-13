# Running Number Types

Types are string identifiers that categorize different sequences of running numbers. Each type maintains its own independent counter.

## Understanding Types

A type is simply a string that identifies what kind of running number you're generating:

```php
running_number()->type('invoice')->generate();  // INVOICE001
running_number()->type('order')->generate();    // ORDER001
running_number()->type('invoice')->generate();  // INVOICE002 (independent sequence)
```

## Defining Types

Types must be defined in your `config/running-number.php` file:

```php
return [
    'types' => [
        'invoice',
        'order',
        'receipt',
        'quote',
    ],
];
```

## Type Naming Conventions

### Recommended Patterns

**Lowercase with underscores:**

```php
'types' => [
    'purchase_order',
    'sales_invoice',
    'credit_note',
],
```

**Why lowercase?** The generator automatically converts types to uppercase by default:

```php
// Input: 'invoice'
// Output: INVOICE001
```

### Naming Best Practices

1. **Be Descriptive**: Use clear, meaningful names

   ```php
   // Good
   'invoice', 'purchase_order', 'delivery_note'

   // Avoid
   'inv', 'po', 'dn'
   ```

2. **Use Singular Form**: Prefer singular over plural

   ```php
   // Good
   'invoice', 'order', 'receipt'

   // Avoid
   'invoices', 'orders', 'receipts'
   ```

3. **Consistent Format**: Stick to one naming pattern

   ```php
   // Good
   'purchase_order', 'sales_order', 'work_order'

   // Mixed (avoid)
   'purchaseOrder', 'sales-order', 'work_order'
   ```

4. **Avoid Special Characters**: Stick to alphanumeric and underscores

   ```php
   // Good
   'credit_note', 'debit_note'

   // Avoid
   'credit-note', 'debit.note', 'credit/note'
   ```

## Common Type Categories

### Financial Documents

```php
'types' => [
    'invoice',
    'quote',
    'receipt',
    'credit_note',
    'debit_note',
    'payment_voucher',
    'purchase_order',
    'sales_order',
],
```

### Customer Management

```php
'types' => [
    'customer',
    'lead',
    'opportunity',
    'contract',
    'support_ticket',
],
```

### Inventory & Assets

```php
'types' => [
    'product',
    'asset',
    'equipment',
    'serial_number',
    'barcode',
],
```

### HR & Personnel

```php
'types' => [
    'employee',
    'applicant',
    'contractor',
    'timesheet',
    'leave_request',
],
```

### Projects & Tasks

```php
'types' => [
    'project',
    'task',
    'milestone',
    'deliverable',
],
```

## Using Enums for Types

### Why Use Enums?

Enums provide:

- **Type Safety**: Catch typos at compile time
- **IDE Autocomplete**: Better developer experience
- **Centralized Management**: Single source of truth
- **Documentation**: Built-in labels and descriptions

### Creating an Enum

```php
<?php

namespace App\Enums;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;

enum DocumentType: string
{
    use InteractsWithEnum;

    case INVOICE = 'invoice';
    case QUOTE = 'quote';
    case RECEIPT = 'receipt';
    case CREDIT_NOTE = 'credit_note';
    case PURCHASE_ORDER = 'purchase_order';

    public function label(): string
    {
        return match($this) {
            self::INVOICE => 'Invoice',
            self::QUOTE => 'Quotation',
            self::RECEIPT => 'Receipt',
            self::CREDIT_NOTE => 'Credit Note',
            self::PURCHASE_ORDER => 'Purchase Order',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::INVOICE => 'Sales invoice for customer billing',
            self::QUOTE => 'Price quotation for potential customers',
            self::RECEIPT => 'Payment receipt acknowledgment',
            self::CREDIT_NOTE => 'Credit note for returns or adjustments',
            self::PURCHASE_ORDER => 'Purchase order to suppliers',
        };
    }
}
```

### Using Enums in Configuration

```php
use App\Enums\DocumentType;

return [
    'types' => DocumentType::values(),
];
```

### Using Enums in Code

```php
use App\Enums\DocumentType;

// Type-safe usage
$invoiceNumber = running_number()
    ->type(DocumentType::INVOICE->value)
    ->generate();

// Get all available types
$types = DocumentType::values();
// ['invoice', 'quote', 'receipt', 'credit_note', 'purchase_order']

// Get labels for UI
$labels = DocumentType::labels();
// ['Invoice', 'Quotation', 'Receipt', 'Credit Note', 'Purchase Order']

// Get options for select dropdowns
$options = DocumentType::options();
// [
//     'invoice' => 'Invoice',
//     'quote' => 'Quotation',
//     ...
// ]
```

See [Enums Guide](03-enums.md) for more details.

## Dynamic Types

### Array-Based Configuration

```php
'types' => [
    'invoice',
    'order',
    'receipt',
],
```

### Merging Multiple Sources

```php
use App\Enums\DocumentType;
use App\Enums\AssetType;

'types' => array_merge(
    DocumentType::values(),
    AssetType::values(),
    ['custom_type']
),
```

### Environment-Based Types

```php
// In config file
'types' => explode(',', env('RUNNING_NUMBER_TYPES', 'invoice,order,receipt')),

// In .env file
RUNNING_NUMBER_TYPES=invoice,order,receipt,quote,credit_note
```

### Loading from Database

```php
use App\Models\DocumentTemplate;

'types' => DocumentTemplate::pluck('code')->toArray(),
```

**Note**: Database-loaded types are cached with config, so remember to clear cache after changes:

```bash
php artisan config:clear
```

## Type Validation

The package automatically validates types against your configuration:

```php
// Configured types: ['invoice', 'order']

// This works
running_number()->type('invoice')->generate(); // INVOICE001

// This throws InvalidRunningNumberTypeException
running_number()->type('receipt')->generate(); // Exception!
```

### Custom Validation

Add custom validation in a service provider:

```php
use CleaniqueCoders\RunningNumber\Generator;

// Validate before generating
public function generateRunningNumber(string $type): string
{
    $allowedTypes = config('running-number.types');

    if (!in_array($type, $allowedTypes)) {
        throw new \InvalidArgumentException("Type '{$type}' is not configured");
    }

    return running_number()->type($type)->generate();
}
```

## Managing Type Prefixes

### Uppercase (Default)

```php
running_number()->type('invoice')->generate();
// Output: INVOICE001
```

### Lowercase

```php
running_number()->type('invoice')->toUpperCase(false)->generate();
// Output: invoice001
```

### Custom Prefix Logic

```php
use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class CustomPresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        // Title case prefix
        $prefix = ucfirst(strtolower($type));
        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}

// Usage
running_number()
    ->type('invoice')
    ->formatter(new CustomPresenter())
    ->generate();
// Output: Invoice00001
```

## Type Organization Strategies

### By Module

```php
// Sales Module
'invoice', 'quote', 'receipt'

// Purchase Module
'purchase_order', 'goods_received', 'supplier_invoice'

// Inventory Module
'stock_transfer', 'stock_adjustment', 'cycle_count'
```

### By Department

```php
// Accounting
'journal_entry', 'payment_voucher', 'expense_claim'

// HR
'employee', 'leave_request', 'payslip'

// Operations
'work_order', 'maintenance_request', 'delivery_order'
```

### By Business Process

```php
// Sales Process
'lead', 'opportunity', 'quote', 'sales_order', 'invoice'

// Procurement Process
'requisition', 'purchase_order', 'goods_receipt', 'supplier_payment'
```

## Best Practices

1. **Plan Your Types**: Define all types upfront before starting development
2. **Document Types**: Add comments in config explaining each type's purpose
3. **Use Enums**: Prefer enums over plain strings for type safety
4. **Consistent Naming**: Follow a consistent naming convention
5. **Avoid Duplication**: Don't create similar types (`invoice` vs `sales_invoice`)
6. **Version Control**: Track type changes in version control
7. **Migration Strategy**: Plan how to add new types in production

## Example: Complete Type Configuration

```php
<?php

use App\Enums\DocumentType;
use App\Enums\AssetType;
use App\Enums\HRType;

return [
    'types' => array_merge(
        // Financial documents
        DocumentType::values(),

        // Asset management
        AssetType::values(),

        // Human resources
        HRType::values(),

        // Custom types
        [
            'project',
            'task',
            'support_ticket',
        ]
    ),

    // ... other configuration
];
```

## Next Steps

- **[Enums](03-enums.md)** - Create type-safe enums for your types
- **[Custom Models](04-custom-models.md)** - Extend the running number model
- **[Usage Guide](../03-usage/01-helper-functions.md)** - Learn how to use types in your code

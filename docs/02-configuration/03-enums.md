# Working with Enums

Learn how to use native PHP 8.1+ enums with Laravel Running Number for type-safe running number type management.

## Why Use Enums?

Enums provide several advantages over plain strings:

- **Type Safety**: Catch typos and invalid types at development time
- **IDE Support**: Full autocomplete and type hinting
- **Self-Documenting**: Code becomes more readable and maintainable
- **Centralized**: Single source of truth for all types
- **Rich Metadata**: Add labels, descriptions, and custom methods

## The Organization Enum

The package includes a default `Organization` enum:

```php
namespace CleaniqueCoders\RunningNumber\Enums;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;

enum Organization: string
{
    use InteractsWithEnum;

    case ORGANIZATION = 'organization';
    case DIVISION = 'division';
    case SECTION = 'section';
    case UNIT = 'unit';
    case PROFILE = 'profile';

    public function label(): string
    {
        return match ($this) {
            self::ORGANIZATION => 'Organization',
            self::DIVISION => 'Division',
            self::SECTION => 'Section',
            self::UNIT => 'Unit',
            self::PROFILE => 'Profile',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ORGANIZATION => 'Main organization entity',
            self::DIVISION => 'Division under organization',
            self::SECTION => 'Section under division',
            self::UNIT => 'Unit under section',
            self::PROFILE => 'User profile identifier',
        };
    }
}
```

## Using the Default Enum

### Basic Usage

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

$number = running_number()
    ->type(Organization::PROFILE->value)
    ->generate();

echo $number; // PROFILE001
```

### Accessing Enum Values

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

// Get the string value
echo Organization::PROFILE->value; // 'profile'

// Get the label
echo Organization::PROFILE->label(); // 'Profile'

// Get the description
echo Organization::PROFILE->description(); // 'User profile identifier'
```

### Getting All Values

```php
// Get all enum values as an array
$values = Organization::values();
// ['organization', 'division', 'section', 'unit', 'profile']

// Get all labels
$labels = Organization::labels();
// ['Organization', 'Division', 'Section', 'Unit', 'Profile']

// Get value => label pairs
$options = Organization::options();
// [
//     'organization' => 'Organization',
//     'division' => 'Division',
//     'section' => 'Section',
//     'unit' => 'Unit',
//     'profile' => 'Profile',
// ]
```

## Creating Custom Enums

### Basic Custom Enum

Create your own enum for document types:

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

### Using Custom Enum in Configuration

Update your `config/running-number.php`:

```php
use App\Enums\DocumentType;

return [
    'types' => DocumentType::values(),
    // ... other configuration
];
```

### Using Custom Enum in Code

```php
use App\Enums\DocumentType;

$invoiceNumber = running_number()
    ->type(DocumentType::INVOICE->value)
    ->generate();

echo $invoiceNumber; // INVOICE001
```

## Advanced Enum Features

### Custom Methods

Add custom logic to your enums:

```php
enum DocumentType: string
{
    use InteractsWithEnum;

    case INVOICE = 'invoice';
    case QUOTE = 'quote';
    case RECEIPT = 'receipt';

    public function prefix(): string
    {
        return match($this) {
            self::INVOICE => 'INV',
            self::QUOTE => 'QUO',
            self::RECEIPT => 'RCP',
        };
    }

    public function requiresApproval(): bool
    {
        return match($this) {
            self::INVOICE => true,
            self::QUOTE => true,
            self::RECEIPT => false,
        };
    }

    public function color(): string
    {
        return match($this) {
            self::INVOICE => 'blue',
            self::QUOTE => 'green',
            self::RECEIPT => 'gray',
        };
    }
}
```

Usage:

```php
$type = DocumentType::INVOICE;

echo $type->prefix(); // 'INV'
echo $type->requiresApproval(); // true
echo $type->color(); // 'blue'
```

### Grouping Cases

Group related cases with additional methods:

```php
enum DocumentType: string
{
    use InteractsWithEnum;

    case INVOICE = 'invoice';
    case CREDIT_NOTE = 'credit_note';
    case DEBIT_NOTE = 'debit_note';
    case QUOTE = 'quote';
    case PURCHASE_ORDER = 'purchase_order';

    public function isAccountingDocument(): bool
    {
        return in_array($this, [
            self::INVOICE,
            self::CREDIT_NOTE,
            self::DEBIT_NOTE,
        ]);
    }

    public function isPurchaseDocument(): bool
    {
        return $this === self::PURCHASE_ORDER;
    }

    public static function accountingTypes(): array
    {
        return [
            self::INVOICE,
            self::CREDIT_NOTE,
            self::DEBIT_NOTE,
        ];
    }
}
```

### Constants and Configuration

```php
enum DocumentType: string
{
    use InteractsWithEnum;

    case INVOICE = 'invoice';
    case QUOTE = 'quote';
    case RECEIPT = 'receipt';

    public function padding(): int
    {
        return match($this) {
            self::INVOICE => 5,
            self::QUOTE => 4,
            self::RECEIPT => 3,
        };
    }

    public function format(): string
    {
        return match($this) {
            self::INVOICE => 'INV-%s',
            self::QUOTE => 'QUO-%s',
            self::RECEIPT => 'RCP-%s',
        };
    }
}
```

## Multiple Enums

You can use multiple enums in your configuration:

```php
use App\Enums\DocumentType;
use App\Enums\AssetType;
use App\Enums\HRType;

return [
    'types' => array_merge(
        DocumentType::values(),
        AssetType::values(),
        HRType::values()
    ),
];
```

Example enums:

```php
// App\Enums\AssetType
enum AssetType: string
{
    use InteractsWithEnum;

    case EQUIPMENT = 'equipment';
    case VEHICLE = 'vehicle';
    case PROPERTY = 'property';
    case FURNITURE = 'furniture';
}

// App\Enums\HRType
enum HRType: string
{
    use InteractsWithEnum;

    case EMPLOYEE = 'employee';
    case CONTRACTOR = 'contractor';
    case LEAVE_REQUEST = 'leave_request';
    case TIMESHEET = 'timesheet';
}
```

## UI Integration

### Dropdown Select

```php
// In your controller
use App\Enums\DocumentType;

public function create()
{
    return view('documents.create', [
        'types' => DocumentType::options()
    ]);
}
```

```blade
<!-- In your view -->
<select name="document_type">
    @foreach($types as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
```

### With Icons

```php
enum DocumentType: string
{
    use InteractsWithEnum;

    case INVOICE = 'invoice';
    case QUOTE = 'quote';
    case RECEIPT = 'receipt';

    public function icon(): string
    {
        return match($this) {
            self::INVOICE => 'file-invoice',
            self::QUOTE => 'file-signature',
            self::RECEIPT => 'receipt',
        };
    }
}
```

```blade
<select name="document_type">
    @foreach(DocumentType::cases() as $type)
        <option value="{{ $type->value }}">
            <i class="fa fa-{{ $type->icon() }}"></i>
            {{ $type->label() }}
        </option>
    @endforeach
</select>
```

## Form Requests & Validation

### Validation Rules

```php
use App\Enums\DocumentType;
use Illuminate\Validation\Rule;

public function rules()
{
    return [
        'document_type' => [
            'required',
            Rule::in(DocumentType::values()),
        ],
    ];
}
```

### Custom Error Messages

```php
public function messages()
{
    return [
        'document_type.in' => 'Please select a valid document type: ' .
            implode(', ', DocumentType::labels()),
    ];
}
```

## Testing with Enums

```php
use App\Enums\DocumentType;
use CleaniqueCoders\RunningNumber\Generator;

it('generates invoice numbers', function () {
    $number = Generator::make()
        ->type(DocumentType::INVOICE->value)
        ->generate();

    expect($number)->toBe('INVOICE001');
});

it('generates numbers for all document types', function () {
    foreach (DocumentType::cases() as $type) {
        $number = Generator::make()
            ->type($type->value)
            ->generate();

        expect($number)->toStartWith(strtoupper($type->value));
    }
});
```

## Best Practices

1. **Use InteractsWithEnum Trait**: Always use the Traitify trait for consistency
2. **Implement label()**: Provide human-readable labels for all cases
3. **Implement description()**: Document what each case is used for
4. **Group Related Cases**: Organize cases logically within the enum
5. **Add Custom Methods**: Enhance enums with domain-specific methods
6. **Type Hint**: Always type-hint enum parameters in methods
7. **Test Coverage**: Write tests for your enum methods and integration

## Example: Complete Enum Implementation

```php
<?php

namespace App\Enums;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;

enum DocumentType: string
{
    use InteractsWithEnum;

    // Cases
    case INVOICE = 'invoice';
    case QUOTE = 'quote';
    case RECEIPT = 'receipt';
    case CREDIT_NOTE = 'credit_note';
    case DEBIT_NOTE = 'debit_note';
    case PURCHASE_ORDER = 'purchase_order';

    // Required methods
    public function label(): string
    {
        return match($this) {
            self::INVOICE => 'Invoice',
            self::QUOTE => 'Quotation',
            self::RECEIPT => 'Receipt',
            self::CREDIT_NOTE => 'Credit Note',
            self::DEBIT_NOTE => 'Debit Note',
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
            self::DEBIT_NOTE => 'Debit note for additional charges',
            self::PURCHASE_ORDER => 'Purchase order to suppliers',
        };
    }

    // Custom methods
    public function prefix(): string
    {
        return match($this) {
            self::INVOICE => 'INV',
            self::QUOTE => 'QUO',
            self::RECEIPT => 'RCP',
            self::CREDIT_NOTE => 'CN',
            self::DEBIT_NOTE => 'DN',
            self::PURCHASE_ORDER => 'PO',
        };
    }

    public function requiresApproval(): bool
    {
        return match($this) {
            self::INVOICE, self::CREDIT_NOTE, self::DEBIT_NOTE => true,
            default => false,
        };
    }

    public function color(): string
    {
        return match($this) {
            self::INVOICE => 'blue',
            self::QUOTE => 'green',
            self::RECEIPT => 'gray',
            self::CREDIT_NOTE => 'orange',
            self::DEBIT_NOTE => 'red',
            self::PURCHASE_ORDER => 'purple',
        };
    }

    // Helper methods
    public static function accountingTypes(): array
    {
        return [
            self::INVOICE,
            self::CREDIT_NOTE,
            self::DEBIT_NOTE,
        ];
    }

    public function isAccountingDocument(): bool
    {
        return in_array($this, self::accountingTypes());
    }
}
```

## Next Steps

- **[Custom Models](04-custom-models.md)** - Extend the running number model
- **[Usage Guide](../03-usage/01-helper-functions.md)** - Learn usage patterns with enums
- **[Custom Presenters](../04-advanced/01-custom-presenters.md)** - Create custom formatters

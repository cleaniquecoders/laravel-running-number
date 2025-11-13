# Core Concepts

Understanding the fundamental concepts behind Laravel Running Number will help you use it more effectively.

## How It Works

Laravel Running Number uses a database-backed approach to generate sequential numbers. Here's what happens when you generate a running number:

1. **Type Validation**: The system checks if the requested type is configured
2. **Record Creation**: If no record exists for the type, one is created
3. **Increment**: The current number is incremented by 1
4. **Format**: The number is formatted using the configured presenter
5. **Return**: The formatted running number is returned

```php
// First call
running_number()->type('invoice')->generate(); // INVOICE001

// Second call
running_number()->type('invoice')->generate(); // INVOICE002

// Third call
running_number()->type('invoice')->generate(); // INVOICE003
```

## Key Components

### 1. Generator

The **Generator** is responsible for creating running numbers. It handles:

- Type validation against configured types
- Database record management
- Number incrementation
- Coordination with the presenter

**Default Implementation**: `CleaniqueCoders\RunningNumber\Generator`

```php
use CleaniqueCoders\RunningNumber\Generator;

$generator = Generator::make()
    ->type('invoice')
    ->generate();
```

### 2. Presenter

The **Presenter** formats the running number output. It determines:

- How the type and number are combined
- Padding configuration
- Final string format

**Default Implementation**: `CleaniqueCoders\RunningNumber\Presenter`

```php
// Default: TYPE + padded number
// INVOICE001, INVOICE002, etc.

// The default presenter uses this format:
public function format($type, $number): string
{
    return $type . str_pad($number, 3, '0', STR_PAD_LEFT);
}
```

### 3. Types

**Types** are string identifiers that categorize running numbers. Each type maintains its own independent sequence.

```php
// Different types = different sequences
running_number()->type('invoice')->generate();  // INVOICE001
running_number()->type('order')->generate();    // ORDER001
running_number()->type('invoice')->generate();  // INVOICE002
```

Types must be defined in your configuration file to be valid.

### 4. Running Number Model

The `RunningNumber` model represents a record in the database:

```php
use CleaniqueCoders\RunningNumber\Models\RunningNumber;

$record = RunningNumber::where('type', 'INVOICE')->first();

echo $record->uuid;       // Unique identifier
echo $record->type;       // "INVOICE"
echo $record->number;     // Current count (e.g., 42)
echo $record->created_at; // Creation timestamp
echo $record->updated_at; // Last increment timestamp
```

## Database Structure

The package stores running number state in a `running_numbers` table:

```
running_numbers
├── id (bigint, primary key)
├── uuid (uuid, unique)
├── type (string)
├── number (integer)
├── reset_period (string, default: 'never')
├── last_reset_at (timestamp, nullable)
├── created_at (timestamp)
└── updated_at (timestamp)
```

Each row represents a unique type with its current sequence number:

| id | uuid | type | number | reset_period | last_reset_at | created_at | updated_at |
|----|------|------|--------|--------------|---------------|------------|------------|
| 1 | ... | INVOICE | 42 | yearly | 2025-01-01 | 2025-01-01 | 2025-11-13 |
| 2 | ... | ORDER | 15 | never | null | 2025-01-05 | 2025-11-12 |
| 3 | ... | PROFILE | 128 | monthly | 2025-11-01 | 2025-01-03 | 2025-11-13 |

## Enums

The package uses native PHP 8.1+ enums with the Traitify package for type management:

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

// Enum cases
Organization::ORGANIZATION  // value: 'organization'
Organization::DIVISION      // value: 'division'
Organization::SECTION       // value: 'section'
Organization::UNIT          // value: 'unit'
Organization::PROFILE       // value: 'profile'

// Enum methods
Organization::values();     // Get all values
Organization::labels();     // Get all labels
Organization::options();    // Get value => label pairs

// Individual enum properties
Organization::PROFILE->value;       // 'profile'
Organization::PROFILE->label();     // 'Profile'
Organization::PROFILE->description(); // 'User profile identifier'
```

## Thread Safety & Race Condition Protection

The package is designed for high-concurrency environments with robust protection against race conditions:

### Database Transactions

All number generation happens within database transactions:

```php
DB::transaction(function () {
    // Create type if needed
    // Lock row for update
    // Increment number
    // Format and return
});
```

### Row Locking

The package uses `lockForUpdate()` to prevent concurrent access:

```php
$running_number = RunningNumber::where('type', 'INVOICE')
    ->lockForUpdate()  // Locks this specific row
    ->first();

$running_number->increment('number');  // Safe from race conditions
```

### Atomic Type Creation

Type creation uses `firstOrCreate()` for atomicity:

```php
// Multiple concurrent requests won't create duplicates
RunningNumber::firstOrCreate(
    ['type' => 'INVOICE'],
    ['number' => 0, 'reset_period' => 'yearly']
);
```

### What This Means

✅ **No Duplicate Numbers**: Even with 100 simultaneous requests
✅ **No Lost Numbers**: Automatic rollback on failure
✅ **Production Ready**: Safe for high-traffic applications
✅ **Database Agnostic**: Works with MySQL, PostgreSQL, SQLite

### Concurrent Request Example

```
Request A: Generates INVOICE001 ✓
Request B: Waits for lock...
Request C: Waits for lock...
Request A: Commits transaction
Request B: Generates INVOICE002 ✓
Request C: Generates INVOICE003 ✓

Result: Sequential, no duplicates, no gaps
```

## Padding

Padding determines how many digits the number portion should have:

```php
// Default padding: 3
config('running-number.padding'); // 3

// Results:
INVOICE001, INVOICE002, ..., INVOICE999, INVOICE1000
```

The number automatically expands beyond the padding when needed:

- With padding 3: `001, 002, ..., 999, 1000, 1001`

## Reset Functionality

The package supports automatic number resetting based on configurable periods:

### Reset Periods

- **NEVER**: Numbers never reset (default behavior)
- **DAILY**: Reset at midnight every day
- **MONTHLY**: Reset on the 1st of each month
- **YEARLY**: Reset on January 1st each year

### Configuration

Set reset periods in your config:

```php
'reset_period' => [
    'default' => 'never',  // Global default

    // Per-type configuration
    'types' => [
        'invoice' => 'yearly',   // Invoices reset yearly
        'receipt' => 'monthly',  // Receipts reset monthly
        'ticket' => 'daily',     // Tickets reset daily
    ],
],
```

### How It Works

The package automatically checks if a reset is needed before incrementing:

```php
// January 2024
running_number()->type('invoice')->generate(); // INVOICE001
running_number()->type('invoice')->generate(); // INVOICE002

// ... time passes to January 2025 ...

// Automatic reset!
running_number()->type('invoice')->generate(); // INVOICE001 (resets)
```

### Manual Reset

You can also reset numbers manually:

```php
use CleaniqueCoders\RunningNumber\Models\RunningNumber;

$record = RunningNumber::where('type', 'INVOICE')->first();
$record->reset(); // Resets to 0 and updates last_reset_at
```

### Reset Period Enum

The package provides a `ResetPeriod` enum:

```php
use CleaniqueCoders\RunningNumber\Enums\ResetPeriod;

ResetPeriod::NEVER   // 'never'
ResetPeriod::DAILY   // 'daily'
ResetPeriod::MONTHLY // 'monthly'
ResetPeriod::YEARLY  // 'yearly'

// Enum methods
ResetPeriod::values();  // Get all period values
ResetPeriod::YEARLY->label();  // 'Yearly Reset'
ResetPeriod::YEARLY->description();  // 'Running number resets on January 1st each year'
```

### Real-World Example

```php
// Invoice numbering with yearly reset
// 2024: INV-2024-001, INV-2024-002, ..., INV-2024-150
// 2025: INV-2025-001, INV-2025-002, ... (resets automatically)

// Configure in config/running-number.php:
'reset_period' => [
    'types' => [
        'invoice' => ResetPeriod::YEARLY->value,
    ],
],
```

## Case Transformation

By default, types are converted to uppercase:

```php
// Input: 'invoice' → Output: INVOICE001
running_number()->type('invoice')->generate();

// Disable uppercase transformation
running_number()->type('invoice')->toUpperCase(false)->generate();
// Output: invoice001
```

## Contracts (Interfaces)

The package defines two contracts for extensibility:

### Generator Contract

```php
namespace CleaniqueCoders\RunningNumber\Contracts;

interface Generator
{
    public static function make(): Generator;
    public function type($type);
    public function toUpperCase($value);
    public function formatter(Presenter $presenter);
    public function generate(): string;
}
```

### Presenter Contract

```php
namespace CleaniqueCoders\RunningNumber\Contracts;

interface Presenter
{
    public function format($type, $number): string;
}
```

These contracts allow you to create custom implementations. See [Advanced Topics](../04-advanced/01-custom-presenters.md) for details.

## Lifecycle

Here's the complete lifecycle of generating a running number:

```
1. User calls running_number()->type('invoice')->generate()
   ↓
2. Generator validates 'invoice' is in configured types
   ↓
3. Generator checks if INVOICE record exists in database
   ↓
4. If not exists: Create new record with number = 0
   ↓
5. Increment number by 1 (atomic operation)
   ↓
6. Save and refresh the record
   ↓
7. Pass type and number to Presenter
   ↓
8. Presenter formats: "INVOICE" + "001" = "INVOICE001"
   ↓
9. Return formatted string to user
```

## Best Practices

1. **Configure Types First**: Always define your types in the config before using them
2. **Use Enums**: Leverage native PHP enums for type-safe type definitions
3. **Model Integration**: Generate numbers in model events for consistency
4. **Don't Manipulate Database**: Never manually modify the `running_numbers` table
5. **Test Sequences**: Write tests to verify your running number generation logic

## Next Steps

- **[Configuration](../02-configuration/01-overview.md)** - Learn how to configure the package
- **[Usage Guide](../03-usage/01-helper-functions.md)** - Explore different usage patterns
- **[Advanced Topics](../04-advanced/01-custom-presenters.md)** - Customize behavior for your needs

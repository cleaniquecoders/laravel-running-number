# Advanced Features

This section covers advanced features of the Laravel Running Number package.

## Topics

- [Date-Based Formats](01-date-based-formats.md) - Format running numbers with date components
- [Multiple Sequences](02-multiple-sequences.md) - Maintain separate sequences using scopes
- [Custom Starting Numbers](03-custom-starting-numbers.md) - Start sequences from specific numbers
- [Number Range Management](04-number-range-management.md) - Set maximum limits for sequences
- [Preview & Bulk Generation](05-preview-and-batch.md) - Preview next numbers and generate in batches

## Quick Examples

### Date-Based Formats

```php
use CleaniqueCoders\RunningNumber\Generator;
use CleaniqueCoders\RunningNumber\Presenters\DatePrefixPresenter;

$number = Generator::make()
    ->type('invoice')
    ->formatter(new DatePrefixPresenter())
    ->generate();
// Output: INVOICE-2025-11-13-001
```

### Multiple Sequences (Scopes)

```php
$retail = Generator::make()
    ->type('invoice')
    ->scope('retail')
    ->generate();
// Output: INVOICE001

$wholesale = Generator::make()
    ->type('invoice')
    ->scope('wholesale')
    ->generate();
// Output: INVOICE001 (separate sequence)
```

### Custom Starting Number

```php
$number = Generator::make()
    ->type('invoice')
    ->startFrom(1000)
    ->generate();
// Output: INVOICE1001
```

### Number Range Management

```php
$number = Generator::make()
    ->type('ticket')
    ->maxNumber(9999)
    ->generate();
// Throws MaxNumberReachedException when limit is reached
```

### Preview Mode

```php
// Preview without incrementing
$preview = Generator::make()
    ->type('invoice')
    ->preview();
// Output: INVOICE002 (current is 001)

// Useful for UI displays
echo "Your next invoice will be: {$preview}";
```

### Bulk Generation

```php
// Generate multiple numbers at once
$tickets = Generator::make()
    ->type('ticket')
    ->generateBatch(10);
// Returns: ['TICKET001', 'TICKET002', ..., 'TICKET010']

// Atomic and efficient
```

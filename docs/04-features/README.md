# Advanced Features

This section covers the advanced features available in Laravel Running Number. These features extend the basic functionality to handle complex business requirements with real-world examples from the codebase.

## 📚 Table of Contents

### [01. Date-Based Formats](01-date-based-formats.md)

Learn how to generate running numbers with date prefixes and time-based formatting.

**Key Topics:**
- DatePrefixPresenter (INVOICE-2025-11-13-001)
- CompactDatePresenter (INVOICE-20251113001)
- YearMonthPresenter (ORDER-2025-11-001)
- Custom date formats and separators
- Combining with reset periods

### [02. Multiple Sequences](02-multiple-sequences.md)

Create independent sequences for the same type using scopes.

**Key Topics:**
- Scope-based sequences
- Multi-tenancy support
- Department/branch separation
- Null vs named scopes
- Combining with other features

### [03. Custom Starting Numbers](03-custom-starting-numbers.md)

Define custom starting points for new running number sequences.

**Key Topics:**
- Setting custom start values
- When starting numbers apply
- Negative starting numbers
- Combining with max numbers
- Migration considerations

### [04. Number Range Management](04-number-range-management.md)

Control the range of generated numbers with maximum limits.

**Key Topics:**
- Setting maximum numbers
- MaxNumberReachedException handling
- Per-scope limits
- Reset period interactions
- Range validation

### [05. Preview and Batch](05-preview-and-batch.md)

Preview next numbers and generate multiple numbers atomically.

**Key Topics:**
- Preview mode (read-only)
- Batch generation
- Atomic operations
- Performance optimization
- Use cases

## 🔍 Quick Examples

### Date-Based Formats

```php
use CleaniqueCoders\RunningNumber\Presenters\DatePrefixPresenter;
use CleaniqueCoders\RunningNumber\Presenters\YearMonthPresenter;

// Date prefix format
$number = running_number()
    ->type('invoice')
    ->formatter(new DatePrefixPresenter())
    ->generate();
// Output: INVOICE-2025-11-13-001

// Year-month format
$number = running_number()
    ->type('order')
    ->formatter(new YearMonthPresenter())
    ->generate();
// Output: ORDER-2025-11-001
```

### Multiple Sequences (Scopes)

```php
// Separate sequences per tenant or department
$retailNumber = running_number()
    ->type('order')
    ->scope('retail')
    ->generate();
// Output: ORDER001

$wholesaleNumber = running_number()
    ->type('order')
    ->scope('wholesale')
    ->generate();
// Output: ORDER001 (independent sequence)
```

### Custom Starting Numbers

```php
// Start from a specific number
$number = running_number()
    ->type('ticket')
    ->startFrom(1000)
    ->generate();
// // First generation: TICKET1001
// Second generation: TICKET1002
```

### Number Range Management

```php
// Define a range with max limit
$number = running_number()
    ->type('voucher')
    ->startFrom(1000)
    ->maxNumber(9999)
    ->generate();
// Range: VOUCHER1001 to VOUCHER9999
// Throws MaxNumberReachedException when limit is reached
```

### Preview Mode

```php
// Preview without incrementing counter (read-only)
$preview = running_number()
    ->type('invoice')
    ->preview();
// Output: INVOICE023 (if current is 022)
// No database changes

// Useful for UI displays
echo "Your next invoice will be: {$preview}";
```

### Bulk Generation

```php
// Generate multiple numbers atomically
$tickets = running_number()
    ->type('ticket')
    ->generateBatch(10);
// Returns: ['TICKET001', 'TICKET002', ..., 'TICKET010']
// All or nothing - atomic transaction

// Efficient for bulk operations
foreach ($tickets as $ticket) {
    Event::create(['ticket_number' => $ticket]);
}
```

## 🎯 Feature Combinations

All features can be combined for powerful functionality:

```php
// Date format + scope + custom start + max number
$number = running_number()
    ->type('invoice')
    ->scope('branch-001')
    ->startFrom(1000)
    ->maxNumber(9999)
    ->formatter(new YearMonthPresenter())
    ->generate();
// Output: INVOICE-2025-11-1001
// Range: 1001-9999 per month, resets monthly
// Independent per branch
```

## 💡 Best Practices

1. **Choose the Right Presenter**: Match format to business requirements
2. **Use Scopes for Isolation**: Separate sequences by tenant, department, or context
3. **Set Appropriate Limits**: Define max numbers to prevent overflow
4. **Preview Before Changes**: Use preview mode for UI display
5. **Batch for Performance**: Generate multiple numbers atomically when needed

## 🔗 Related Documentation

- [Configuration](../02-configuration/) - Configure feature behavior
- [Usage Patterns](../03-usage/) - Common implementation patterns
- [Advanced Topics](../05-advanced/) - Custom presenters and generators

---

[← Back to Main Documentation](../README.md)

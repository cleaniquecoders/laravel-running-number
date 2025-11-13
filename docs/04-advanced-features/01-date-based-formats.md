# Date-Based Formats

Generate running numbers with date components embedded in the format. This is useful for creating human-readable, time-organized identifiers.

## Available Presenters

### DatePrefixPresenter

Adds a full date prefix to the running number.

```php
use CleaniqueCoders\RunningNumber\Generator;
use CleaniqueCoders\RunningNumber\Presenters\DatePrefixPresenter;

$number = Generator::make()
    ->type('invoice')
    ->formatter(new DatePrefixPresenter())
    ->generate();

// Output: INVOICE-2025-11-13-001
```

#### Custom Date Format

```php
$presenter = new DatePrefixPresenter('Y/m/d', '/');

$number = Generator::make()
    ->type('order')
    ->formatter($presenter)
    ->generate();

// Output: ORDER/2025/11/13/001
```

**Constructor Parameters:**
- `$dateFormat` (string): PHP date format string (default: 'Y-m-d')
- `$separator` (string): Separator between parts (default: '-')

### CompactDatePresenter

Creates compact date formats without separators in the date portion.

```php
use CleaniqueCoders\RunningNumber\Presenters\CompactDatePresenter;

$number = Generator::make()
    ->type('receipt')
    ->formatter(new CompactDatePresenter())
    ->generate();

// Output: RECEIPT-20251113001
```

#### Custom Format

```php
$presenter = new CompactDatePresenter('Ym', '_');

$number = Generator::make()
    ->type('ticket')
    ->formatter($presenter)
    ->generate();

// Output: TICKET_202511001
```

**Constructor Parameters:**
- `$dateFormat` (string): PHP date format string (default: 'Ymd')
- `$separator` (string): Separator between type and date (default: '-')

### YearMonthPresenter

Formats with separate year and month components.

```php
use CleaniqueCoders\RunningNumber\Presenters\YearMonthPresenter;

$number = Generator::make()
    ->type('invoice')
    ->formatter(new YearMonthPresenter())
    ->generate();

// Output: INVOICE-2025-11-001
```

#### Custom Separator

```php
$presenter = new YearMonthPresenter('/');

$number = Generator::make()
    ->type('order')
    ->formatter($presenter)
    ->generate();

// Output: ORDER/2025/11/001
```

**Constructor Parameters:**
- `$separator` (string): Separator between parts (default: '-')

## Use Cases

### Monthly Invoice Numbering

```php
use CleaniqueCoders\RunningNumber\Presenters\YearMonthPresenter;

$invoice = Generator::make()
    ->type('invoice')
    ->formatter(new YearMonthPresenter())
    ->generate();

// January 2025: INVOICE-2025-01-001
// February 2025: INVOICE-2025-02-001
```

### Daily Ticket System

```php
use CleaniqueCoders\RunningNumber\Presenters\DatePrefixPresenter;

$ticket = Generator::make()
    ->type('ticket')
    ->formatter(new DatePrefixPresenter('Y-m-d', '-'))
    ->generate();

// Output: TICKET-2025-11-13-001
```

### Compact Receipt Numbers

```php
use CleaniqueCoders\RunningNumber\Presenters\CompactDatePresenter;

$receipt = Generator::make()
    ->type('receipt')
    ->formatter(new CompactDatePresenter('Ymd', ''))
    ->generate();

// Output: RECEIPT20251113001
```

## Combining with Reset Periods

Date-based formats work seamlessly with reset periods:

```php
// Configure in config/running-number.php
'reset_period' => [
    'types' => [
        'invoice' => 'monthly',
    ],
],

// Usage
use CleaniqueCoders\RunningNumber\Presenters\YearMonthPresenter;

$invoice = Generator::make()
    ->type('invoice')
    ->formatter(new YearMonthPresenter())
    ->generate();

// Automatically resets each month
// Jan 2025: INVOICE-2025-01-001, INVOICE-2025-01-002, ...
// Feb 2025: INVOICE-2025-02-001, INVOICE-2025-02-002, ... (reset)
```

## Configuration

### Padding

The number padding configuration still applies:

```php
// config/running-number.php
'padding' => 5,

// Usage
$number = Generator::make()
    ->type('order')
    ->formatter(new DatePrefixPresenter())
    ->generate();

// Output: ORDER-2025-11-13-00001
```

## Creating Custom Date Presenters

You can create your own date-based presenter by implementing the `Presenter` contract:

```php
use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class CustomDatePresenter implements Presenter
{
    public function format($type, $number): string
    {
        $padding = config('running-number.padding', 3);
        $paddedNumber = str_pad($number, $padding, '0', STR_PAD_LEFT);

        $year = date('Y');
        $week = date('W');

        return "{$type}-W{$week}-{$year}-{$paddedNumber}";
        // Example: INVOICE-W46-2025-001
    }
}
```

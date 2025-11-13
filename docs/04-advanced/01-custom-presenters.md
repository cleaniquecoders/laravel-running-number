# Custom Presenters

Learn how to create custom presenters to format running numbers according to your specific requirements.

## Understanding Presenters

A presenter is responsible for formatting the final running number output. It takes a type and number, and returns a formatted string.

## Default Presenter

The default presenter format is:

```php
TYPE001, TYPE002, TYPE003, ...
```

Implementation:

```php
class Presenter implements Contract
{
    public function format($type, $number): string
    {
        return $type . str_pad($number, config('running-number.padding'), '0', STR_PAD_LEFT);
    }
}
```

## Creating a Custom Presenter

### Step 1: Implement the Contract

```php
<?php

namespace App\Presenters;

use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class CustomPresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        // Your custom format logic
        return sprintf('%s-%04d', $type, $number);
    }
}
```

### Step 2: Use Your Presenter

#### Per-Instance Usage

```php
use App\Presenters\CustomPresenter;

$number = running_number()
    ->type('invoice')
    ->formatter(new CustomPresenter())
    ->generate();
// Output: INVOICE-0001
```

#### Global Configuration

Update `config/running-number.php`:

```php
'presenter' => \App\Presenters\CustomPresenter::class,
```

## Presenter Examples

### Date-Based Format

```php
class DatePresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        return sprintf(
            '%s/%s/%05d',
            $type,
            date('Ymd'),
            $number
        );
    }
}

// Output: INVOICE/20251113/00001
```

### Year-Month Format

```php
class YearMonthPresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        return sprintf(
            '%s-%s-%04d',
            $type,
            date('Ym'),
            $number
        );
    }
}

// Output: INVOICE-202511-0001
```

### Prefix-Based Format

```php
class PrefixPresenter implements Presenter
{
    private array $prefixes = [
        'INVOICE' => 'INV',
        'ORDER' => 'ORD',
        'RECEIPT' => 'RCP',
        'QUOTE' => 'QUO',
    ];

    public function format(string $type, int $number): string
    {
        $prefix = $this->prefixes[$type] ?? substr($type, 0, 3);

        return sprintf(
            '%s-%06d',
            $prefix,
            $number
        );
    }
}

// Output: INV-000001, ORD-000001
```

### Department-Code Format

```php
class DepartmentPresenter implements Presenter
{
    public function __construct(
        private string $department
    ) {}

    public function format(string $type, int $number): string
    {
        return sprintf(
            '%s-%s-%04d',
            strtoupper($this->department),
            $type,
            $number
        );
    }
}

// Usage
$number = running_number()
    ->type('invoice')
    ->formatter(new DepartmentPresenter('sales'))
    ->generate();
// Output: SALES-INVOICE-0001
```

### Checksum Format

```php
class ChecksumPresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        $formatted = sprintf('%s%04d', $type, $number);
        $checksum = $this->calculateChecksum($formatted);

        return $formatted . $checksum;
    }

    private function calculateChecksum(string $value): int
    {
        return array_sum(str_split($value)) % 10;
    }
}

// Output: INVOICE00015 (5 is checksum)
```

### Hierarchical Format

```php
class HierarchicalPresenter implements Presenter
{
    public function __construct(
        private ?string $parent = null
    ) {}

    public function format(string $type, int $number): string
    {
        $parts = [$type];

        if ($this->parent) {
            array_unshift($parts, $this->parent);
        }

        $parts[] = str_pad($number, 4, '0', STR_PAD_LEFT);

        return implode('.', $parts);
    }
}

// Usage
$number = running_number()
    ->type('task')
    ->formatter(new HierarchicalPresenter('PROJECT001'))
    ->generate();
// Output: PROJECT001.TASK.0001
```

### Custom Separator Format

```php
class SeparatorPresenter implements Presenter
{
    public function __construct(
        private string $separator = '-',
        private int $padding = 4
    ) {}

    public function format(string $type, int $number): string
    {
        return sprintf(
            '%s%s%s',
            $type,
            $this->separator,
            str_pad($number, $this->padding, '0', STR_PAD_LEFT)
        );
    }
}

// Usage
$number = running_number()
    ->type('invoice')
    ->formatter(new SeparatorPresenter('/', 5))
    ->generate();
// Output: INVOICE/00001
```

### Multi-Segment Format

```php
class MultiSegmentPresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        return sprintf(
            '%s-%s-%s-%04d',
            substr($type, 0, 3),        // First 3 chars of type
            date('y'),                   // Year (2 digits)
            date('m'),                   // Month
            $number                      // Padded number
        );
    }
}

// Output: INV-25-11-0001
```

## Advanced Patterns

### Conditional Formatting

```php
class ConditionalPresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        // Different format based on type
        return match($type) {
            'INVOICE', 'QUOTE' => sprintf('%s-%s-%05d', $type, date('Y'), $number),
            'RECEIPT' => sprintf('%s%06d', $type, $number),
            default => sprintf('%s-%04d', $type, $number),
        };
    }
}
```

### Localized Format

```php
class LocalizedPresenter implements Presenter
{
    public function __construct(
        private string $locale = 'en'
    ) {}

    public function format(string $type, int $number): string
    {
        $translations = [
            'en' => ['INVOICE' => 'INV', 'ORDER' => 'ORD'],
            'fr' => ['INVOICE' => 'FAC', 'ORDER' => 'CMD'],
        ];

        $prefix = $translations[$this->locale][$type] ?? $type;

        return sprintf('%s-%05d', $prefix, $number);
    }
}
```

### Configurable Presenter

```php
class ConfigurablePresenter implements Presenter
{
    public function __construct(
        private array $config = []
    ) {
        $this->config = array_merge([
            'separator' => '-',
            'padding' => 4,
            'include_date' => false,
            'date_format' => 'Ymd',
            'prefix_map' => [],
        ], $config);
    }

    public function format(string $type, int $number): string
    {
        $prefix = $this->config['prefix_map'][$type] ?? $type;
        $parts = [$prefix];

        if ($this->config['include_date']) {
            $parts[] = date($this->config['date_format']);
        }

        $parts[] = str_pad(
            $number,
            $this->config['padding'],
            '0',
            STR_PAD_LEFT
        );

        return implode($this->config['separator'], $parts);
    }
}

// Usage
$presenter = new ConfigurablePresenter([
    'separator' => '/',
    'padding' => 5,
    'include_date' => true,
    'date_format' => 'Y-m',
    'prefix_map' => [
        'INVOICE' => 'INV',
        'ORDER' => 'ORD',
    ],
]);

$number = running_number()
    ->type('invoice')
    ->formatter($presenter)
    ->generate();
// Output: INV/2025-11/00001
```

## Testing Custom Presenters

```php
use App\Presenters\CustomPresenter;

it('formats numbers correctly', function () {
    $presenter = new CustomPresenter();

    $result = $presenter->format('INVOICE', 1);

    expect($result)->toBe('INVOICE-0001');
});

it('handles different types', function () {
    $presenter = new CustomPresenter();

    expect($presenter->format('INVOICE', 1))->toBe('INVOICE-0001')
        ->and($presenter->format('ORDER', 42))->toBe('ORDER-0042')
        ->and($presenter->format('RECEIPT', 999))->toBe('RECEIPT-0999');
});
```

## Best Practices

1. **Implement the Contract**: Always implement the `Presenter` contract
2. **Keep It Simple**: Focus on formatting, not business logic
3. **Make It Testable**: Write unit tests for your presenters
4. **Document Format**: Clearly document the output format
5. **Handle Edge Cases**: Consider large numbers, special characters
6. **Performance**: Keep formatting logic efficient
7. **Immutable**: Presenters should be stateless when possible

## Next Steps

- [Custom Generators](02-custom-generators.md) - Customize generation logic
- [Integration Patterns](03-integration-patterns.md) - Advanced integration

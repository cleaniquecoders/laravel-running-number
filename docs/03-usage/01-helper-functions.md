# Helper Functions

The `running_number()` helper function provides the easiest way to generate running numbers.

## Basic Usage

```php
$number = running_number()->type('invoice')->generate();
echo $number; // INVOICE001
```

## Available Methods

### `type(string $type)`

Set the running number type:

```php
running_number()->type('invoice')->generate();
running_number()->type('order')->generate();
running_number()->type('receipt')->generate();
```

### `toUpperCase(bool $value)`

Control case transformation (default: `true`):

```php
// Uppercase (default)
running_number()->type('invoice')->generate();
// Output: INVOICE001

// Lowercase
running_number()->type('invoice')->toUpperCase(false)->generate();
// Output: invoice001
```

### `formatter(Presenter $presenter)`

Use a custom presenter:

```php
use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class CustomPresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        return sprintf('%s-%04d', $type, $number);
    }
}

$number = running_number()
    ->type('invoice')
    ->formatter(new CustomPresenter())
    ->generate();
// Output: INVOICE-0001
```

### `generate()`

Generate and return the running number:

```php
$number = running_number()->type('invoice')->generate();
```

## Method Chaining

All methods support fluent chaining:

```php
$number = running_number()
    ->type('invoice')
    ->toUpperCase(true)
    ->generate();
```

## Using with Enums

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

$number = running_number()
    ->type(Organization::PROFILE->value)
    ->generate();
```

## Examples

### Invoice Numbers

```php
$invoice1 = running_number()->type('invoice')->generate(); // INVOICE001
$invoice2 = running_number()->type('invoice')->generate(); // INVOICE002
$invoice3 = running_number()->type('invoice')->generate(); // INVOICE003
```

### Multiple Types

```php
$invoice = running_number()->type('invoice')->generate();  // INVOICE001
$order = running_number()->type('order')->generate();      // ORDER001
$receipt = running_number()->type('receipt')->generate();  // RECEIPT001
```

### Lowercase Format

```php
$number = running_number()
    ->type('ticket')
    ->toUpperCase(false)
    ->generate();
// Output: ticket001
```

## Best Practices

1. **Use Type Constants**: Define types in configuration or enums
2. **Consistent Casing**: Stick to one casing style across your application
3. **Error Handling**: Wrap in try-catch for invalid types
4. **Type Validation**: Validate types before passing to helper

## Next Steps

- [Generator Class](02-generator-class.md) - Alternative usage pattern
- [Model Integration](04-model-integration.md) - Use in Eloquent models

# Testing

Learn how to test running number generation in your Laravel application.

## Basic Testing

### Testing with Pest

```php
use CleaniqueCoders\RunningNumber\Generator;
use CleaniqueCoders\RunningNumber\Enums\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates sequential numbers', function () {
    $number1 = Generator::make()->type(Organization::PROFILE->value)->generate();
    $number2 = Generator::make()->type(Organization::PROFILE->value)->generate();
    $number3 = Generator::make()->type(Organization::PROFILE->value)->generate();

    expect($number1)->toBe('PROFILE001')
        ->and($number2)->toBe('PROFILE002')
        ->and($number3)->toBe('PROFILE003');
});

it('maintains separate sequences for different types', function () {
    $invoice1 = Generator::make()->type('invoice')->generate();
    $order1 = Generator::make()->type('order')->generate();
    $invoice2 = Generator::make()->type('invoice')->generate();

    expect($invoice1)->toBe('INVOICE001')
        ->and($order1)->toBe('ORDER001')
        ->and($invoice2)->toBe('INVOICE002');
});
```

### Testing with PHPUnit

```php
use Tests\TestCase;
use CleaniqueCoders\RunningNumber\Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RunningNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_sequential_numbers()
    {
        $number1 = Generator::make()->type('invoice')->generate();
        $number2 = Generator::make()->type('invoice')->generate();

        $this->assertEquals('INVOICE001', $number1);
        $this->assertEquals('INVOICE002', $number2);
    }
}
```

## Testing Model Integration

```php
it('automatically generates number on model creation', function () {
    $invoice = Invoice::create([
        'customer_id' => 1,
        'amount' => 100.00,
    ]);

    expect($invoice->invoice_number)->not->toBeNull()
        ->and($invoice->invoice_number)->toStartWith('INVOICE');
});
```

## Testing Custom Presenters

```php
use App\Presenters\CustomPresenter;

it('formats numbers correctly', function () {
    $presenter = new CustomPresenter();

    $result = $presenter->format('INVOICE', 1);

    expect($result)->toBe('INVOICE-0001');
});
```

## Best Practices

1. **Use RefreshDatabase**: Always refresh database in tests
2. **Test Sequences**: Verify sequential generation
3. **Test Edge Cases**: Test with large numbers, special characters
4. **Isolate Tests**: Each test should be independent
5. **Mock External Calls**: Mock any external dependencies

## Running Tests

```bash
# Run all tests
composer test

# Run specific test file
./vendor/bin/pest tests/RunningNumberTest.php

# Run with coverage
composer test-coverage
```

## Next Steps

- [Contributing](02-contributing.md) - Contribute to the package
- [Development Setup](03-development-setup.md) - Set up development environment

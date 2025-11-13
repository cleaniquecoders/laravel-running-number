# Generator Class

The `Generator` class provides direct access to running number generation with full control.

## Basic Usage

```php
use CleaniqueCoders\RunningNumber\Generator;

$generator = Generator::make();
$number = $generator->type('invoice')->generate();
```

## Creating an Instance

### Static Make Method

```php
use CleaniqueCoders\RunningNumber\Generator;

$generator = Generator::make();
```

### Direct Instantiation

```php
use CleaniqueCoders\RunningNumber\Generator;

$generator = new Generator();
```

## Methods

### `make()`

Create a new generator instance:

```php
$generator = Generator::make();
```

### `type(string $type)`

Set the type for generation:

```php
$generator->type('invoice');
```

### `toUpperCase(bool $value)`

Control case transformation:

```php
$generator->toUpperCase(false); // Lowercase output
```

### `formatter(Presenter $presenter)`

Set a custom presenter:

```php
use CleaniqueCoders\RunningNumber\Contracts\Presenter;

$generator->formatter(new CustomPresenter());
```

### `scope(?string $scope)`

Set a scope for multiple sequences per type:

```php
$generator->scope('retail');
```

See [Multiple Sequences](../04-advanced-features/02-multiple-sequences.md) for details.

### `startFrom(int $number)`

Set a custom starting number:

```php
$generator->startFrom(1000);
```

See [Custom Starting Numbers](../04-advanced-features/03-custom-starting-numbers.md) for details.

### `maxNumber(int $number)`

Set a maximum number limit:

```php
$generator->maxNumber(9999);
```

See [Number Range Management](../04-advanced-features/04-number-range-management.md) for details.

### `generate()`

Generate the running number:

```php
$number = $generator->generate();
```

### `preview()`

Preview the next number without incrementing:

```php
$preview = $generator->preview();
```

See [Preview & Bulk Generation](../04-advanced-features/05-preview-and-batch.md#preview-mode) for details.

### `generateBatch(int $count)`

Generate multiple numbers at once:

```php
$numbers = $generator->generateBatch(10);
// Returns array of 10 numbers
```

See [Preview & Bulk Generation](../04-advanced-features/05-preview-and-batch.md#bulk-generation) for details.

## Usage Patterns

### Fluent Interface

```php
use CleaniqueCoders\RunningNumber\Generator;

$number = Generator::make()
    ->type('invoice')
    ->toUpperCase(true)
    ->generate();
```

### With Advanced Features

```php
use CleaniqueCoders\RunningNumber\Generator;
use CleaniqueCoders\RunningNumber\Presenters\YearMonthPresenter;

$number = Generator::make()
    ->type('invoice')
    ->scope('retail')
    ->startFrom(1000)
    ->maxNumber(9999)
    ->formatter(new YearMonthPresenter())
    ->generate();
// Output: INVOICE-2025-11-1001
```

### Reusable Instance

```php
$generator = Generator::make();

$invoice1 = $generator->type('invoice')->generate();
$invoice2 = $generator->type('invoice')->generate();
$order1 = $generator->type('order')->generate();
```

### With Custom Formatter

```php
use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class DatePresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        return sprintf(
            '%s/%s/%04d',
            $type,
            date('Ym'),
            $number
        );
    }
}

$number = Generator::make()
    ->type('invoice')
    ->formatter(new DatePresenter())
    ->generate();
// Output: INVOICE/202511/0001
```

## Dependency Injection

### In Controllers

```php
use CleaniqueCoders\RunningNumber\Contracts\Generator;

class InvoiceController extends Controller
{
    public function store(Request $request, Generator $generator)
    {
        $invoiceNumber = $generator
            ->type('invoice')
            ->generate();

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'customer_id' => $request->customer_id,
            'amount' => $request->amount,
        ]);

        return response()->json($invoice);
    }
}
```

### In Services

```php
use CleaniqueCoders\RunningNumber\Contracts\Generator;

class InvoiceService
{
    public function __construct(
        private Generator $generator
    ) {}

    public function createInvoice(array $data): Invoice
    {
        $data['invoice_number'] = $this->generator
            ->type('invoice')
            ->generate();

        return Invoice::create($data);
    }
}
```

## Testing with Generator

```php
use CleaniqueCoders\RunningNumber\Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates sequential invoice numbers', function () {
    $number1 = Generator::make()->type('invoice')->generate();
    $number2 = Generator::make()->type('invoice')->generate();
    $number3 = Generator::make()->type('invoice')->generate();

    expect($number1)->toBe('INVOICE001')
        ->and($number2)->toBe('INVOICE002')
        ->and($number3)->toBe('INVOICE003');
});
```

## Best Practices

1. **Use Dependency Injection**: Inject the Generator contract in services
2. **Reuse Instances**: Create once, use multiple times
3. **Custom Formatters**: Encapsulate formatting logic in presenter classes
4. **Type Safety**: Use enums for type values
5. **Error Handling**: Always handle potential exceptions

## Next Steps

- [Facade](03-facade.md) - Using the facade pattern
- [Model Integration](04-model-integration.md) - Integrate with Eloquent

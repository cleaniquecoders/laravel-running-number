# Facade Usage

The `RunningNumber` facade provides a static interface for generating running numbers.

## Basic Usage

```php
use CleaniqueCoders\RunningNumber\Facades\RunningNumber;

$number = RunningNumber::type('invoice')->generate();
```

## Importing the Facade

```php
use CleaniqueCoders\RunningNumber\Facades\RunningNumber;
```

## Available Methods

All Generator methods are available through the facade:

```php
// Basic generation
RunningNumber::type('invoice')->generate();

// With case control
RunningNumber::type('invoice')->toUpperCase(false)->generate();

// With custom formatter
RunningNumber::type('invoice')->formatter(new CustomPresenter())->generate();
```

## Usage Examples

### Generate Invoice Number

```php
use CleaniqueCoders\RunningNumber\Facades\RunningNumber;

$invoiceNumber = RunningNumber::type('invoice')->generate();
// Output: INVOICE001
```

### Generate Multiple Types

```php
$invoice = RunningNumber::type('invoice')->generate();
$order = RunningNumber::type('order')->generate();
$receipt = RunningNumber::type('receipt')->generate();
```

### Lowercase Format

```php
$number = RunningNumber::type('receipt')->toUpperCase(false)->generate();
// Output: receipt001
```

### With Enums

```php
use CleaniqueCoders\RunningNumber\Facades\RunningNumber;
use CleaniqueCoders\RunningNumber\Enums\Organization;

$number = RunningNumber::type(Organization::PROFILE->value)->generate();
```

## In Controllers

```php
use CleaniqueCoders\RunningNumber\Facades\RunningNumber;

class InvoiceController extends Controller
{
    public function store(Request $request)
    {
        $invoice = Invoice::create([
            'invoice_number' => RunningNumber::type('invoice')->generate(),
            'customer_id' => $request->customer_id,
            'amount' => $request->amount,
        ]);

        return response()->json($invoice);
    }
}
```

## In Services

```php
use CleaniqueCoders\RunningNumber\Facades\RunningNumber;

class DocumentService
{
    public function generateDocument(string $type, array $data): Document
    {
        $data['document_number'] = RunningNumber::type($type)->generate();

        return Document::create($data);
    }
}
```

## In Jobs

```php
use CleaniqueCoders\RunningNumber\Facades\RunningNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class GenerateInvoice implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function handle()
    {
        $invoice = Invoice::create([
            'invoice_number' => RunningNumber::type('invoice')->generate(),
            // ... other fields
        ]);
    }
}
```

## Testing with Facade

```php
use CleaniqueCoders\RunningNumber\Facades\RunningNumber;

it('generates invoice number via facade', function () {
    $number = RunningNumber::type('invoice')->generate();

    expect($number)->toBe('INVOICE001');
});
```

## Comparison: Facade vs Helper

### Using Facade

```php
use CleaniqueCoders\RunningNumber\Facades\RunningNumber;

$number = RunningNumber::type('invoice')->generate();
```

### Using Helper

```php
$number = running_number()->type('invoice')->generate();
```

Both approaches are equivalent. Choose based on your preference:

- **Facade**: More explicit, easier to test with mocks
- **Helper**: More concise, less verbose

## Best Practices

1. **Import at Top**: Always import the facade at the top of the file
2. **Consistent Usage**: Stick to either facade or helper throughout your codebase
3. **IDE Support**: Facades provide better IDE autocomplete
4. **Testing**: Facades are easier to mock in tests
5. **Type Safety**: Use with enums for type-safe code

## Next Steps

- [Model Integration](04-model-integration.md) - Integrate with Eloquent models
- [Common Scenarios](05-common-scenarios.md) - Real-world examples

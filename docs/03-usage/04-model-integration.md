# Model Integration

Learn how to integrate running numbers seamlessly with your Eloquent models.

## Basic Integration

### Using Model Events

The most common approach is to generate running numbers in model events:

```php
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['invoice_number', 'customer_id', 'amount'];

    protected static function booted()
    {
        static::creating(function ($invoice) {
            $invoice->invoice_number = running_number()
                ->type('invoice')
                ->generate();
        });
    }
}
```

Now every new invoice automatically gets a running number:

```php
$invoice = Invoice::create([
    'customer_id' => 1,
    'amount' => 100.00,
]);

echo $invoice->invoice_number; // INVOICE001
```

## Using Different Events

### On Creating (Before Save)

```php
protected static function booted()
{
    static::creating(function ($model) {
        $model->number = running_number()->type('order')->generate();
    });
}
```

### On Created (After Save)

```php
protected static function booted()
{
    static::created(function ($model) {
        $model->update([
            'reference' => running_number()->type('reference')->generate()
        ]);
    });
}
```

### Conditional Generation

```php
protected static function booted()
{
    static::creating(function ($invoice) {
        // Only generate if not already set
        if (empty($invoice->invoice_number)) {
            $invoice->invoice_number = running_number()
                ->type('invoice')
                ->generate();
        }
    });
}
```

## Model with Enum

```php
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['document_number', 'type', 'content'];

    protected $casts = [
        'type' => DocumentType::class,
    ];

    protected static function booted()
    {
        static::creating(function ($document) {
            $document->document_number = running_number()
                ->type($document->type->value)
                ->generate();
        });
    }
}
```

Usage:

```php
$invoice = Document::create([
    'type' => DocumentType::INVOICE,
    'content' => 'Invoice content',
]);

echo $invoice->document_number; // INVOICE001
```

## Multiple Running Numbers

### Multiple Fields

```php
class Order extends Model
{
    protected static function booted()
    {
        static::creating(function ($order) {
            $order->order_number = running_number()
                ->type('order')
                ->generate();

            $order->tracking_number = running_number()
                ->type('tracking')
                ->generate();
        });
    }
}
```

### Based on Status

```php
class Document extends Model
{
    protected static function booted()
    {
        static::creating(function ($document) {
            $type = $document->is_internal ? 'internal_doc' : 'external_doc';
            $document->document_number = running_number()
                ->type($type)
                ->generate();
        });
    }
}
```

## Custom Methods

### Regenerate Number

```php
class Invoice extends Model
{
    public function regenerateNumber(): void
    {
        $this->invoice_number = running_number()
            ->type('invoice')
            ->generate();

        $this->save();
    }
}
```

### Preview Next Number

```php
class Invoice extends Model
{
    public static function previewNextNumber(): string
    {
        return running_number()
            ->type('invoice')
            ->generate();
    }
}
```

Note: This will actually increment the counter. For true preview, you'd need to query the database without incrementing.

## With Traits

### Reusable Trait

```php
namespace App\Traits;

trait HasRunningNumber
{
    protected static function bootHasRunningNumber()
    {
        static::creating(function ($model) {
            $type = $model->getRunningNumberType();
            $field = $model->getRunningNumberField();

            if (empty($model->{$field})) {
                $model->{$field} = running_number()
                    ->type($type)
                    ->generate();
            }
        });
    }

    abstract protected function getRunningNumberType(): string;

    protected function getRunningNumberField(): string
    {
        return 'number';
    }
}
```

Usage:

```php
class Invoice extends Model
{
    use HasRunningNumber;

    protected function getRunningNumberType(): string
    {
        return 'invoice';
    }

    protected function getRunningNumberField(): string
    {
        return 'invoice_number';
    }
}

class Order extends Model
{
    use HasRunningNumber;

    protected function getRunningNumberType(): string
    {
        return 'order';
    }

    protected function getRunningNumberField(): string
    {
        return 'order_number';
    }
}
```

## Polymorphic Models

```php
class Numberable extends Model
{
    protected $fillable = ['numberable_type', 'numberable_id', 'number'];

    public function numberable()
    {
        return $this->morphTo();
    }
}

class Invoice extends Model
{
    public function numberable()
    {
        return $this->morphOne(Numberable::class, 'numberable');
    }

    protected static function booted()
    {
        static::created(function ($invoice) {
            $invoice->numberable()->create([
                'number' => running_number()->type('invoice')->generate()
            ]);
        });
    }
}
```

## Database Transactions

Ensure running number generation is part of your database transaction:

```php
use Illuminate\Support\Facades\DB;

public function createInvoice(array $data): Invoice
{
    return DB::transaction(function () use ($data) {
        $invoice = new Invoice($data);
        $invoice->invoice_number = running_number()->type('invoice')->generate();
        $invoice->save();

        // Create line items, etc.

        return $invoice;
    });
}
```

## Validation

### Ensure Uniqueness

```php
use Illuminate\Validation\Rule;

public function rules()
{
    return [
        'invoice_number' => [
            'required',
            'string',
            Rule::unique('invoices', 'invoice_number'),
        ],
    ];
}
```

### Custom Validation Rule

```php
use Illuminate\Contracts\Validation\Rule;

class ValidRunningNumber implements Rule
{
    public function passes($attribute, $value)
    {
        // Validate format: TYPE001, TYPE002, etc.
        return preg_match('/^[A-Z]+\d{3,}$/', $value);
    }

    public function message()
    {
        return 'The :attribute must be a valid running number format.';
    }
}
```

## Testing

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('automatically generates invoice number on creation', function () {
    $invoice = Invoice::create([
        'customer_id' => 1,
        'amount' => 100.00,
    ]);

    expect($invoice->invoice_number)->not->toBeNull()
        ->and($invoice->invoice_number)->toStartWith('INVOICE');
});

it('generates sequential numbers', function () {
    $invoice1 = Invoice::create(['customer_id' => 1, 'amount' => 100]);
    $invoice2 = Invoice::create(['customer_id' => 2, 'amount' => 200]);
    $invoice3 = Invoice::create(['customer_id' => 3, 'amount' => 300]);

    expect($invoice1->invoice_number)->toBe('INVOICE001')
        ->and($invoice2->invoice_number)->toBe('INVOICE002')
        ->and($invoice3->invoice_number)->toBe('INVOICE003');
});
```

## Best Practices

1. **Use Model Events**: Generate numbers in `creating` event for consistency
2. **Check Existence**: Only generate if field is empty
3. **Transactions**: Wrap in database transactions for data integrity
4. **Validation**: Validate format and uniqueness
5. **Traits**: Create reusable traits for common patterns
6. **Testing**: Always test number generation in your models
7. **Soft Deletes**: Consider soft deletes instead of hard deletes to preserve number sequences

## Next Steps

- [Common Scenarios](05-common-scenarios.md) - Real-world usage examples
- [Custom Presenters](../04-advanced/01-custom-presenters.md) - Custom formatting

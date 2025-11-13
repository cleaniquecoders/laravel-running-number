# Common Scenarios

Real-world examples of using Laravel Running Number in various business scenarios.

## Financial Documents

### Invoice Generation

```php
class Invoice extends Model
{
    protected $fillable = ['customer_id', 'invoice_number', 'amount', 'issued_at'];

    protected static function booted()
    {
        static::creating(function ($invoice) {
            $invoice->invoice_number = running_number()
                ->type('invoice')
                ->generate();
            $invoice->issued_at = now();
        });
    }
}

// Usage
$invoice = Invoice::create([
    'customer_id' => 123,
    'amount' => 1500.00,
]);
// Generates: INVOICE001, INVOICE002, etc.
```

### Multi-Type Documents

```php
class FinancialDocument extends Model
{
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

// Usage
$invoice = FinancialDocument::create([
    'type' => DocumentType::INVOICE,
    'amount' => 1000,
]);
// Generates: INVOICE001

$creditNote = FinancialDocument::create([
    'type' => DocumentType::CREDIT_NOTE,
    'amount' => 100,
]);
// Generates: CREDITNOTE001
```

## E-Commerce

### Order Numbers

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

$order = Order::create([
    'customer_id' => 456,
    'total' => 250.00,
]);
// order_number: ORDER001
// tracking_number: TRACKING001
```

### Product SKU

```php
class Product extends Model
{
    protected static function booted()
    {
        static::creating(function ($product) {
            if (empty($product->sku)) {
                $product->sku = running_number()
                    ->type('product')
                    ->generate();
            }
        });
    }
}
```

## Customer Management

### Customer ID

```php
class Customer extends Model
{
    protected static function booted()
    {
        static::creating(function ($customer) {
            $customer->customer_code = running_number()
                ->type('customer')
                ->generate();
        });
    }
}

$customer = Customer::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
// customer_code: CUSTOMER001
```

### Support Tickets

```php
class Ticket extends Model
{
    protected static function booted()
    {
        static::creating(function ($ticket) {
            $ticket->ticket_number = running_number()
                ->type('ticket')
                ->generate();
        });
    }
}

$ticket = Ticket::create([
    'customer_id' => 1,
    'subject' => 'Issue with order',
    'description' => 'Product not received',
]);
// ticket_number: TICKET001
```

## Asset Management

### Asset Tracking

```php
class Asset extends Model
{
    protected $casts = [
        'type' => AssetType::class,
    ];

    protected static function booted()
    {
        static::creating(function ($asset) {
            $asset->asset_tag = running_number()
                ->type($asset->type->value)
                ->generate();
        });
    }
}

$laptop = Asset::create([
    'type' => AssetType::EQUIPMENT,
    'name' => 'Dell Laptop XPS 15',
]);
// asset_tag: EQUIPMENT001
```

## HR & Payroll

### Employee Numbers

```php
class Employee extends Model
{
    protected static function booted()
    {
        static::creating(function ($employee) {
            $employee->employee_number = running_number()
                ->type('employee')
                ->generate();
        });
    }
}

$employee = Employee::create([
    'first_name' => 'Jane',
    'last_name' => 'Smith',
    'email' => 'jane@company.com',
]);
// employee_number: EMPLOYEE001
```

### Leave Requests

```php
class LeaveRequest extends Model
{
    protected static function booted()
    {
        static::creating(function ($leave) {
            $leave->reference_number = running_number()
                ->type('leave')
                ->generate();
        });
    }
}
```

## Project Management

### Project Codes

```php
class Project extends Model
{
    protected static function booted()
    {
        static::creating(function ($project) {
            $project->project_code = running_number()
                ->type('project')
                ->generate();
        });
    }
}

$project = Project::create([
    'name' => 'Website Redesign',
    'client_id' => 5,
]);
// project_code: PROJECT001
```

### Task Numbers

```php
class Task extends Model
{
    protected static function booted()
    {
        static::creating(function ($task) {
            $task->task_number = running_number()
                ->type('task')
                ->generate();
        });
    }
}
```

## Inventory Management

### Stock Transfer

```php
class StockTransfer extends Model
{
    protected static function booted()
    {
        static::creating(function ($transfer) {
            $transfer->transfer_number = running_number()
                ->type('transfer')
                ->generate();
        });
    }
}

$transfer = StockTransfer::create([
    'from_warehouse_id' => 1,
    'to_warehouse_id' => 2,
    'items' => [...],
]);
// transfer_number: TRANSFER001
```

## Advanced Patterns

### Year-Based Numbering

```php
use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class YearPresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        return sprintf(
            '%s-%s-%04d',
            $type,
            date('Y'),
            $number
        );
    }
}

class Invoice extends Model
{
    protected static function booted()
    {
        static::creating(function ($invoice) {
            $invoice->invoice_number = running_number()
                ->type('invoice')
                ->formatter(new YearPresenter())
                ->generate();
        });
    }
}
// Generates: INVOICE-2025-0001
```

### Department-Based Numbering

```php
class Document extends Model
{
    protected static function booted()
    {
        static::creating(function ($document) {
            $type = $document->department . '_doc';
            $document->document_number = running_number()
                ->type($type)
                ->generate();
        });
    }
}

$hrDoc = Document::create(['department' => 'hr', 'title' => 'Policy']);
// document_number: HR_DOC001

$financeDoc = Document::create(['department' => 'finance', 'title' => 'Report']);
// document_number: FINANCE_DOC001
```

### Conditional Formatting

```php
class Order extends Model
{
    protected static function booted()
    {
        static::creating(function ($order) {
            $type = $order->is_wholesale ? 'wholesale' : 'retail';
            $order->order_number = running_number()
                ->type($type)
                ->toUpperCase(!$order->is_wholesale) // Uppercase for retail only
                ->generate();
        });
    }
}
```

## API Integration

### REST API Endpoint

```php
use CleaniqueCoders\RunningNumber\Facades\RunningNumber;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:invoice,quote,receipt',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $document = Document::create([
            'document_number' => RunningNumber::type($validated['type'])->generate(),
            'type' => $validated['type'],
            'customer_id' => $validated['customer_id'],
            'amount' => $validated['amount'],
        ]);

        return response()->json($document, 201);
    }
}
```

### Preview Next Number

```php
class DocumentController extends Controller
{
    public function preview(string $type)
    {
        // Note: This will actually increment the counter
        // For true preview, query the database directly
        $nextNumber = RunningNumber::type($type)->generate();

        return response()->json([
            'next_number' => $nextNumber
        ]);
    }
}
```

## Multi-Tenant Applications

```php
class Invoice extends Model
{
    protected static function booted()
    {
        static::creating(function ($invoice) {
            $type = 'tenant_' . auth()->user()->tenant_id . '_invoice';
            $invoice->invoice_number = running_number()
                ->type($type)
                ->generate();
        });
    }
}

// Tenant 1: TENANT_1_INVOICE001
// Tenant 2: TENANT_2_INVOICE001
```

## Best Practices from Real Use Cases

1. **Document Type Separation**: Keep different document types separate
2. **Year/Period Reset**: Consider yearly resets for better organization
3. **Soft Deletes**: Use soft deletes to preserve number sequences
4. **Audit Trail**: Log number generation for compliance
5. **Error Handling**: Always wrap in try-catch for production
6. **Testing**: Test number generation in integration tests
7. **Database Backup**: Regular backups of running_numbers table
8. **Transaction Wrap**: Always use database transactions

## Next Steps

- [Custom Presenters](../04-advanced/01-custom-presenters.md) - Create custom formatters
- [Custom Generators](../04-advanced/02-custom-generators.md) - Extend generation logic

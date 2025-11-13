# Preview Mode & Bulk Generation

Two powerful features for managing running numbers: preview the next number without incrementing, and generate multiple numbers at once.

## Preview Mode

Get a preview of what the next running number will be without actually incrementing the counter. This is useful for UI displays, form validation, and user feedback.

### Basic Usage

```php
use CleaniqueCoders\RunningNumber\Generator;

// Generate first invoice
$invoice = Generator::make()
    ->type('invoice')
    ->generate();
// Output: INVOICE001

// Preview the next number (doesn't increment)
$preview = Generator::make()
    ->type('invoice')
    ->preview();
// Output: INVOICE002

// Preview again - still shows 002
$preview2 = Generator::make()
    ->type('invoice')
    ->preview();
// Output: INVOICE002

// Actually generate - now it's 002
$next = Generator::make()
    ->type('invoice')
    ->generate();
// Output: INVOICE002
```

### How It Works

The `preview()` method is **completely read-only**:

1. Queries the database for existing record (no writes)
2. If record doesn't exist, shows what first number would be based on `startFrom()`
3. If record exists, reads current counter state
4. Accounts for pending resets
5. Calculates what the next number would be
6. Formats and returns it
7. **No database modifications** - No record creation, no transactions, no locks
8. Pure read operation - safe to call anytime without side effects

### Use Cases

#### Form Validation

```php
public function validateInvoiceForm(Request $request)
{
    $nextNumber = Generator::make()
        ->type('invoice')
        ->preview();

    return view('invoice.form', [
        'next_invoice_number' => $nextNumber
    ]);
}
```

#### Display to Users

```php
<div class="alert alert-info">
    Your next order number will be:
    <strong>{{ Generator::make()->type('order')->preview() }}</strong>
</div>
```

#### Confirmation Screens

```php
public function confirmOrder()
{
    $preview = Generator::make()
        ->type('order')
        ->scope(auth()->user()->tenant_id)
        ->preview();

    return view('confirm', [
        'order_number' => $preview
    ]);
}
```

### With Scopes

Preview works independently per scope:

```php
$retail = Generator::make()
    ->type('invoice')
    ->scope('retail')
    ->preview();
// Output: INVOICE001

$wholesale = Generator::make()
    ->type('invoice')
    ->scope('wholesale')
    ->preview();
// Output: INVOICE001 (different scope)
```

### With Date-Based Formats

```php
use CleaniqueCoders\RunningNumber\Presenters\YearMonthPresenter;

$preview = Generator::make()
    ->type('invoice')
    ->formatter(new YearMonthPresenter())
    ->preview();
// Output: INVOICE-2025-11-001
```

### Accounting for Resets

Preview automatically accounts for pending resets:

```php
// If reset is due (e.g., daily reset from yesterday)
$preview = Generator::make()
    ->type('ticket')
    ->preview();
// Output: TICKET001 (would reset on next generation)
```

### For New Types

Preview works even on brand new types:

```php
// First time previewing this type
$preview = Generator::make()
    ->type('newtype')
    ->preview();
// Output: NEWTYPE001
```

## Bulk Generation

Generate multiple running numbers at once in a single atomic operation. This is much more efficient than generating numbers one by one.

### Basic Usage

```php
use CleaniqueCoders\RunningNumber\Generator;

$numbers = Generator::make()
    ->type('ticket')
    ->generateBatch(5);

// Returns array:
// ['TICKET001', 'TICKET002', 'TICKET003', 'TICKET004', 'TICKET005']
```

### How It Works

The `generateBatch(int $count)` method:
1. Checks max number limits for the entire batch
2. Locks the database row (thread-safe)
3. Checks if reset is needed
4. Calculates all numbers at once
5. Updates the counter once (not per number)
6. Returns array of formatted numbers
7. All operations are atomic (all or nothing)

### Performance Benefits

Instead of 5 database operations:

```php
// Slower - 5 separate transactions
$numbers = [];
for ($i = 0; $i < 5; $i++) {
    $numbers[] = Generator::make()->type('ticket')->generate();
}
```

Use bulk generation:

```php
// Faster - 1 transaction
$numbers = Generator::make()
    ->type('ticket')
    ->generateBatch(5);
```

### Use Cases

#### Event Ticketing

```php
public function reserveTickets($eventId, $quantity)
{
    try {
        $tickets = Generator::make()
            ->type('ticket')
            ->scope("event-{$eventId}")
            ->maxNumber($event->capacity)
            ->generateBatch($quantity);

        foreach ($tickets as $ticketNumber) {
            Ticket::create([
                'ticket_number' => $ticketNumber,
                'event_id' => $eventId,
            ]);
        }

        return $tickets;
    } catch (MaxNumberReachedException $e) {
        return response()->json(['error' => 'Not enough tickets available'], 422);
    }
}
```

#### Bulk Order Processing

```php
public function processBulkOrders(array $orders)
{
    $orderNumbers = Generator::make()
        ->type('order')
        ->generateBatch(count($orders));

    foreach ($orders as $index => $order) {
        $order['order_number'] = $orderNumbers[$index];
        Order::create($order);
    }
}
```

#### Pre-generating Numbers

```php
// Pre-generate invoice numbers for the month
$invoiceNumbers = Generator::make()
    ->type('invoice')
    ->generateBatch(100);

Cache::put('pregen_invoices_' . date('Y-m'), $invoiceNumbers, now()->endOfMonth());
```

### With Scopes

```php
$retail = Generator::make()
    ->type('invoice')
    ->scope('retail')
    ->generateBatch(10);

$wholesale = Generator::make()
    ->type('invoice')
    ->scope('wholesale')
    ->generateBatch(5);

// Each scope maintains independent sequences
```

### With Custom Starting Numbers

```php
$numbers = Generator::make()
    ->type('order')
    ->startFrom(1000)
    ->generateBatch(3);
// Returns: ['ORDER1001', 'ORDER1002', 'ORDER1003']
```

### With Max Number Limits

Batch generation respects max number limits:

```php
try {
    // Will check if all 100 numbers can be generated
    $tickets = Generator::make()
        ->type('ticket')
        ->maxNumber(50)
        ->generateBatch(100); // Throws exception!
} catch (MaxNumberReachedException $e) {
    // Handle capacity exceeded
}

// This works - within limits
$tickets = Generator::make()
    ->type('ticket')
    ->maxNumber(50)
    ->generateBatch(50);
```

### With Date-Based Formats

```php
use CleaniqueCoders\RunningNumber\Presenters\CompactDatePresenter;

$invoices = Generator::make()
    ->type('invoice')
    ->formatter(new CompactDatePresenter())
    ->generateBatch(3);

// Returns:
// ['INVOICE-20251113001', 'INVOICE-20251113002', 'INVOICE-20251113003']
```

### With Reset Periods

Batch generation accounts for resets:

```php
// If reset is due, batch starts from reset value
$numbers = Generator::make()
    ->type('daily-ticket')
    ->generateBatch(5);

// If daily reset was needed:
// Returns: ['DAILY-TICKET001', 'DAILY-TICKET002', ...]
```

### Edge Cases

#### Empty Batch

```php
$empty = Generator::make()
    ->type('order')
    ->generateBatch(0);
// Returns: []

$negative = Generator::make()
    ->type('order')
    ->generateBatch(-5);
// Returns: []
```

#### Atomic Operations

Batch generation is fully atomic:

```php
// All 10 numbers are generated together
Generator::make()
    ->type('order')
    ->generateBatch(10);

// Next single generation continues from 11
$next = Generator::make()
    ->type('order')
    ->generate();
// Returns: ORDER011
```

## Best Practices

### Preview Mode

1. **Completely Read-Only**: No database writes, no record creation, no transactions
2. **UI Displays**: Show users what their next number will be
3. **Validation**: Validate forms with preview data
4. **No Side Effects**: Preview doesn't affect the counter or database state
5. **Concurrent Safe**: Multiple previews don't interfere with each other
6. **Performance**: Lightweight operation with no locking overhead
7. **Caching**: Safe to cache preview results as they have no side effects
8. **New Types**: Shows what first number would be for non-existent types

### Bulk Generation

1. **Batch Size**: Keep batches reasonable (avoid 10,000+)
2. **Error Handling**: Always handle `MaxNumberReachedException`
3. **Transaction Safety**: Wrap in try-catch for business logic
4. **Resource Planning**: Check capacity before generating
5. **Atomicity**: Use batches instead of loops for efficiency

## Examples

### Reservation System

```php
class TicketReservationService
{
    public function reserveTickets($eventId, $quantity)
    {
        $event = Event::findOrFail($eventId);

        // Preview to show user
        $nextNumber = Generator::make()
            ->type('ticket')
            ->scope("event-{$eventId}")
            ->preview();

        // Actually reserve (with capacity check)
        try {
            $ticketNumbers = Generator::make()
                ->type('ticket')
                ->scope("event-{$eventId}")
                ->maxNumber($event->capacity)
                ->generateBatch($quantity);

            return [
                'tickets' => $ticketNumbers,
                'first_ticket' => $ticketNumbers[0],
                'last_ticket' => end($ticketNumbers),
            ];
        } catch (MaxNumberReachedException $e) {
            throw new Exception('Event is at capacity');
        }
    }

    public function checkAvailability($eventId)
    {
        $model = config('running-number.model');
        $record = $model::where('type', 'TICKET')
            ->where('scope', "event-{$eventId}")
            ->first();

        $used = $record ? $record->number : 0;
        $capacity = Event::find($eventId)->capacity;

        return [
            'used' => $used,
            'remaining' => $capacity - $used,
            'next_number' => Generator::make()
                ->type('ticket')
                ->scope("event-{$eventId}")
                ->preview(),
        ];
    }
}
```

### Invoice Pre-Generation

```php
class InvoiceService
{
    public function pregenerateMonthlyInvoices()
    {
        // Generate 500 invoice numbers for the month
        $numbers = Generator::make()
            ->type('invoice')
            ->startFrom((int) date('y') * 100000) // Year-based
            ->generateBatch(500);

        Cache::put(
            'pregen_invoices_' . date('Y-m'),
            $numbers,
            now()->endOfMonth()
        );

        return $numbers;
    }

    public function getNextInvoicePreview()
    {
        // Show preview in UI
        return Generator::make()
            ->type('invoice')
            ->formatter(new YearMonthPresenter())
            ->preview();
    }
}
```

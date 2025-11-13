# Number Range Management

Set maximum limits for running number sequences to prevent unlimited growth and enforce business rules. When the maximum is reached, an exception is thrown.

## Basic Usage

```php
use CleaniqueCoders\RunningNumber\Generator;
use CleaniqueCoders\RunningNumber\Exceptions\MaxNumberReachedException;

try {
    $number = Generator::make()
        ->type('ticket')
        ->maxNumber(9999)
        ->generate();
} catch (MaxNumberReachedException $e) {
    // Handle max number reached
    Log::error("Ticket sequence full: " . $e->getMessage());
}
```

## How It Works

The `maxNumber()` method sets an upper limit for the sequence:

```php
// Generate tickets numbered 1-5
for ($i = 1; $i <= 5; $i++) {
    Generator::make()
        ->type('ticket')
        ->maxNumber(5)
        ->generate();
}
// Outputs: TICKET001, TICKET002, TICKET003, TICKET004, TICKET005

// Sixth attempt throws exception
Generator::make()
    ->type('ticket')
    ->maxNumber(5)
    ->generate();
// Throws: MaxNumberReachedException
```

## Exception Handling

### Basic Exception Handling

```php
try {
    $number = Generator::make()
        ->type('order')
        ->maxNumber(1000)
        ->generate();

    return $number;
} catch (MaxNumberReachedException $e) {
    return response()->json([
        'error' => 'Order sequence full',
        'message' => $e->getMessage()
    ], 422);
}
```

### Custom Error Messages

```php
try {
    $number = Generator::make()
        ->type('invoice')
        ->maxNumber(99999)
        ->generate();
} catch (MaxNumberReachedException $e) {
    // Exception message: "Maximum number 99999 reached for type INVOICE"
    throw new BusinessException(
        'Invoice numbering capacity reached. Please contact support.'
    );
}
```

### Logging and Alerts

```php
try {
    $number = Generator::make()
        ->type('ticket')
        ->maxNumber(10000)
        ->generate();
} catch (MaxNumberReachedException $e) {
    Log::critical('Ticket sequence exhausted', [
        'type' => 'TICKET',
        'max' => 10000,
    ]);

    // Send alert to administrators
    Notification::route('mail', 'admin@example.com')
        ->notify(new SequenceFullNotification('TICKET'));

    throw $e;
}
```

## Common Use Cases

### Daily Ticket System

```php
// config/running-number.php
'reset_period' => [
    'types' => [
        'ticket' => 'daily',
    ],
],

// Usage - resets daily, max 500 per day
$ticket = Generator::make()
    ->type('ticket')
    ->maxNumber(500)
    ->generate();
```

### Limited Capacity Events

```php
public function issueEventTicket($eventId)
{
    try {
        return Generator::make()
            ->type('ticket')
            ->scope("event-{$eventId}")
            ->maxNumber($event->capacity)
            ->generate();
    } catch (MaxNumberReachedException $e) {
        throw new EventFullException('Event has reached maximum capacity');
    }
}
```

### Prevent Database Growth

```php
// Limit sequences to prevent unbounded growth
$number = Generator::make()
    ->type('temp-id')
    ->maxNumber(999999)
    ->generate();
```

## Combining with Other Features

### With Custom Starting Numbers

Define a specific range:

```php
// Range: 1000-2000 (allows 1000 numbers)
for ($i = 1; $i <= 1000; $i++) {
    $number = Generator::make()
        ->type('order')
        ->startFrom(1000)
        ->maxNumber(2000)
        ->generate();
}
// Generates: ORDER1001 to ORDER2000

// Next attempt throws exception
Generator::make()
    ->type('order')
    ->startFrom(1000)
    ->maxNumber(2000)
    ->generate();
// Throws MaxNumberReachedException
```

### With Scopes

Each scope can have its own maximum:

```php
// VIP customers: limited slots
$vip = Generator::make()
    ->type('membership')
    ->scope('vip')
    ->maxNumber(100)
    ->generate();

// Regular customers: more slots
$regular = Generator::make()
    ->type('membership')
    ->scope('regular')
    ->maxNumber(10000)
    ->generate();
```

### With Reset Periods

Maximum is checked after reset:

```php
// config/running-number.php
'reset_period' => [
    'types' => [
        'daily-ticket' => 'daily',
    ],
],

// Maximum of 500 tickets per day
$ticket = Generator::make()
    ->type('daily-ticket')
    ->maxNumber(500)
    ->generate();

// Automatically resets next day, allowing another 500
```

### With Date-Based Formats

```php
use CleaniqueCoders\RunningNumber\Presenters\YearMonthPresenter;

$invoice = Generator::make()
    ->type('invoice')
    ->maxNumber(99999)
    ->formatter(new YearMonthPresenter())
    ->generate();
// Output: INVOICE-2025-11-00001
// Throws exception after: INVOICE-2025-11-99999
```

## Model Integration

```php
class Ticket extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            try {
                $ticket->ticket_number = Generator::make()
                    ->type('ticket')
                    ->scope($ticket->event_id)
                    ->maxNumber($ticket->event->capacity)
                    ->generate();
            } catch (MaxNumberReachedException $e) {
                throw new \Exception('Event is at full capacity');
            }
        });
    }
}
```

## Monitoring and Alerts

### Check Remaining Capacity

```php
use CleaniqueCoders\RunningNumber\Models\RunningNumber;

function getRemainingCapacity($type, $max, $scope = null)
{
    $query = RunningNumber::where('type', strtoupper($type));

    if ($scope !== null) {
        $query->where('scope', $scope);
    } else {
        $query->whereNull('scope');
    }

    $record = $query->first();

    if (!$record) {
        return $max; // Not started yet
    }

    return max(0, $max - $record->number);
}

// Usage
$remaining = getRemainingCapacity('ticket', 500, 'event-123');

if ($remaining < 50) {
    // Alert: running low
    Log::warning("Only {$remaining} tickets remaining");
}
```

### Pre-check Before Generation

```php
function canGenerateTicket($eventId, $maxCapacity)
{
    $current = RunningNumber::where('type', 'TICKET')
        ->where('scope', "event-{$eventId}")
        ->value('number') ?? 0;

    return $current < $maxCapacity;
}

if (canGenerateTicket($eventId, $event->capacity)) {
    $ticket = Generator::make()
        ->type('ticket')
        ->scope("event-{$eventId}")
        ->maxNumber($event->capacity)
        ->generate();
} else {
    return response()->json(['error' => 'Event full'], 422);
}
```

## Best Practices

1. **Always Handle Exceptions**: Wrap max number generation in try-catch blocks
2. **Log Limit Reached**: Log when limits are hit for monitoring
3. **Use with Reset Periods**: Combine with reset periods for recurring capacity limits
4. **Validate Range**: Ensure `startFrom` < `maxNumber`
5. **Monitor Capacity**: Implement monitoring for sequences approaching limits
6. **Set Realistic Limits**: Choose limits that align with business requirements

## Advanced Examples

### Tiered Membership System

```php
class Membership
{
    public static function generate($tier)
    {
        $limits = [
            'bronze' => 10000,
            'silver' => 5000,
            'gold' => 1000,
            'platinum' => 100,
        ];

        try {
            return Generator::make()
                ->type('membership')
                ->scope($tier)
                ->maxNumber($limits[$tier])
                ->generate();
        } catch (MaxNumberReachedException $e) {
            throw new TierFullException("No more {$tier} memberships available");
        }
    }
}
```

### Event Ticketing with Zones

```php
foreach ($event->zones as $zone) {
    try {
        $ticket = Generator::make()
            ->type('ticket')
            ->scope("event-{$event->id}-zone-{$zone->code}")
            ->maxNumber($zone->capacity)
            ->generate();

        Ticket::create([
            'ticket_number' => $ticket,
            'event_id' => $event->id,
            'zone_id' => $zone->id,
        ]);
    } catch (MaxNumberReachedException $e) {
        return response()->json([
            'error' => "Zone {$zone->code} is full"
        ], 422);
    }
}
```

### Department Budget Codes

```php
// Each department has limited budget codes
$budgetCode = Generator::make()
    ->type('budget-code')
    ->scope($department)
    ->startFrom($department->code_start)
    ->maxNumber($department->code_end)
    ->generate();
```

## Without Max Number

If you don't need a maximum, simply don't call `maxNumber()`:

```php
// Unlimited sequence
$number = Generator::make()
    ->type('invoice')
    ->generate();
// Will continue indefinitely
```

## Resetting When Full

If you need to reset manually when max is reached:

```php
use CleaniqueCoders\RunningNumber\Models\RunningNumber;

try {
    $number = Generator::make()
        ->type('ticket')
        ->maxNumber(1000)
        ->generate();
} catch (MaxNumberReachedException $e) {
    // Reset the sequence
    $record = RunningNumber::where('type', 'TICKET')->first();
    $record->reset();

    // Try again
    $number = Generator::make()
        ->type('ticket')
        ->maxNumber(1000)
        ->generate();
}
```

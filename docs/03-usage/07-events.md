# Events

Listen to running number generation events for auditing, logging, and notifications.

## RunningNumberGenerated Event

The `RunningNumberGenerated` event is automatically dispatched after a running number is successfully generated.

### Event Structure

```php
namespace CleaniqueCoders\RunningNumber\Events;

use CleaniqueCoders\RunningNumber\Models\RunningNumber;

class RunningNumberGenerated
{
    public function __construct(
        public string $type,
        public string $formattedNumber,
        public RunningNumber $model,
        public ?string $scope = null,
        public ?int $number = null,
    ) {}
}
```

### Event Properties

| Property | Type | Description |
|----------|------|-------------|
| `type` | `string` | The type identifier (e.g., "INVOICE") |
| `formattedNumber` | `string` | The formatted running number (e.g., "INVOICE001") |
| `model` | `RunningNumber` | The database model instance |
| `scope` | `string\|null` | The scope identifier if used |
| `number` | `int\|null` | The raw number value |

## Listening to Events

### Using Event Listeners

Create a listener class:

```php
namespace App\Listeners;

use CleaniqueCoders\RunningNumber\Events\RunningNumberGenerated;
use Illuminate\Support\Facades\Log;

class LogRunningNumberGeneration
{
    public function handle(RunningNumberGenerated $event): void
    {
        Log::info('Running number generated', [
            'type' => $event->type,
            'number' => $event->formattedNumber,
            'scope' => $event->scope,
            'uuid' => $event->model->uuid,
        ]);
    }
}
```

Register in `EventServiceProvider`:

```php
use App\Listeners\LogRunningNumberGeneration;
use CleaniqueCoders\RunningNumber\Events\RunningNumberGenerated;

protected $listen = [
    RunningNumberGenerated::class => [
        LogRunningNumberGeneration::class,
    ],
];
```

### Using Closures

For simple cases, use closure-based listeners:

```php
use CleaniqueCoders\RunningNumber\Events\RunningNumberGenerated;
use Illuminate\Support\Facades\Event;

Event::listen(RunningNumberGenerated::class, function (RunningNumberGenerated $event) {
    logger()->info("Generated: {$event->formattedNumber}");
});
```

### In Service Providers

Add listeners in your service provider's `boot` method:

```php
use CleaniqueCoders\RunningNumber\Events\RunningNumberGenerated;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(RunningNumberGenerated::class, function ($event) {
        // Your logic here
        activity()
            ->log("Generated running number: {$event->formattedNumber}");
    });
}
```

## Use Cases

### 1. Audit Logging

Track all running number generation:

```php
use CleaniqueCoders\RunningNumber\Events\RunningNumberGenerated;
use Illuminate\Support\Facades\Event;

Event::listen(RunningNumberGenerated::class, function ($event) {
    \App\Models\AuditLog::create([
        'action' => 'running_number_generated',
        'type' => $event->type,
        'number' => $event->formattedNumber,
        'scope' => $event->scope,
        'uuid' => $event->model->uuid,
        'created_at' => now(),
    ]);
});
```

### 2. Notifications

Send notifications when specific numbers are generated:

```php
use CleaniqueCoders\RunningNumber\Events\RunningNumberGenerated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

Event::listen(RunningNumberGenerated::class, function ($event) {
    if ($event->type === 'INVOICE' && $event->model->number >= 1000) {
        Notification::route('slack', config('slack.webhook'))
            ->notify(new MilestoneReached($event->formattedNumber));
    }
});
```

### 3. Cache Invalidation

Invalidate caches when numbers change:

```php
Event::listen(RunningNumberGenerated::class, function ($event) {
    cache()->forget("running_numbers:{$event->type}");
    cache()->forget("latest_number:{$event->type}");
});
```

### 4. Real-time Updates

Broadcast to frontend via websockets:

```php
use CleaniqueCoders\RunningNumber\Events\RunningNumberGenerated;
use Illuminate\Support\Facades\Event;

Event::listen(RunningNumberGenerated::class, function ($event) {
    broadcast(new \App\Events\NumberGenerated(
        $event->type,
        $event->formattedNumber,
        $event->scope
    ))->toOthers();
});
```

### 5. Metrics Collection

Track generation metrics:

```php
Event::listen(RunningNumberGenerated::class, function ($event) {
    Metrics::increment('running_numbers.generated', [
        'type' => $event->type,
        'scope' => $event->scope ?? 'default',
    ]);
});
```

### 6. External System Integration

Sync with external systems:

```php
Event::listen(RunningNumberGenerated::class, function ($event) {
    if ($event->type === 'ORDER') {
        Http::post('https://erp.example.com/api/sync', [
            'order_number' => $event->formattedNumber,
            'scope' => $event->scope,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
});
```

## Accessing Model Data

The event includes the full `RunningNumber` model instance:

```php
Event::listen(RunningNumberGenerated::class, function ($event) {
    $model = $event->model;

    // Access model properties
    dump([
        'uuid' => $model->uuid,
        'type' => $model->type,
        'scope' => $model->scope,
        'number' => $model->number,
        'reset_period' => $model->reset_period,
        'last_reset_at' => $model->last_reset_at,
        'created_at' => $model->created_at,
        'updated_at' => $model->updated_at,
    ]);
});
```

## Event Serialization

The event includes a `toArray()` method for easy serialization:

```php
Event::listen(RunningNumberGenerated::class, function ($event) {
    $data = $event->toArray();

    // Returns:
    // [
    //     'type' => 'INVOICE',
    //     'scope' => 'retail',
    //     'number' => 42,
    //     'formatted_number' => 'INVOICE042',
    //     'uuid' => '123e4567-e89b-12d3-a456-426614174000',
    //     'reset_period' => 'monthly',
    //     'last_reset_at' => '2025-11-01T00:00:00+00:00',
    //     'created_at' => '2025-01-15T10:30:15+00:00',
    // ]

    // Store or send the data
    Redis::lpush('number_history', json_encode($data));
});
```

## Conditional Listeners

Only listen to specific types or scopes:

```php
Event::listen(RunningNumberGenerated::class, function ($event) {
    // Only for invoices
    if ($event->type !== 'INVOICE') {
        return;
    }

    // Only for retail scope
    if ($event->scope !== 'retail') {
        return;
    }

    // Your logic here
    sendInvoiceNotification($event->formattedNumber);
});
```

## Queued Listeners

For expensive operations, use queued listeners:

```php
namespace App\Listeners;

use CleaniqueCoders\RunningNumber\Events\RunningNumberGenerated;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncToExternalSystem implements ShouldQueue
{
    public function handle(RunningNumberGenerated $event): void
    {
        // This will be processed in the background
        ExternalAPI::sync([
            'type' => $event->type,
            'number' => $event->formattedNumber,
        ]);
    }
}
```

## Testing with Events

### Faking Events

In tests, you can fake events:

```php
use CleaniqueCoders\RunningNumber\Events\RunningNumberGenerated;
use Illuminate\Support\Facades\Event;

test('generates running number', function () {
    Event::fake([RunningNumberGenerated::class]);

    $number = running_number()->type('invoice')->generate();

    Event::assertDispatched(RunningNumberGenerated::class, function ($event) {
        return $event->type === 'INVOICE';
    });
});
```

### Capturing Event Data

Capture event data in tests:

```php
test('event includes correct data', function () {
    $captured = null;

    Event::listen(RunningNumberGenerated::class, function ($event) use (&$captured) {
        $captured = $event;
    });

    $number = running_number()->type('invoice')->generate();

    expect($captured)->not->toBeNull()
        ->and($captured->formattedNumber)->toBe($number)
        ->and($captured->type)->toBe('INVOICE');
});
```

## Best Practices

1. **Keep listeners focused**: Each listener should do one thing well

```php
// Good: Focused responsibility
Event::listen(RunningNumberGenerated::class, LogNumberGeneration::class);
Event::listen(RunningNumberGenerated::class, NotifyAdmins::class);
Event::listen(RunningNumberGenerated::class, UpdateCache::class);

// Avoid: Doing everything in one listener
Event::listen(RunningNumberGenerated::class, DoEverything::class);
```

2. **Use queued listeners for slow operations**

```php
// Sync with external API asynchronously
class SyncToExternalAPI implements ShouldQueue
{
    public function handle(RunningNumberGenerated $event): void
    {
        // Slow API call handled in background
    }
}
```

3. **Handle failures gracefully**

```php
public function handle(RunningNumberGenerated $event): void
{
    try {
        ExternalAPI::sync($event->toArray());
    } catch (\Exception $e) {
        Log::error('Failed to sync running number', [
            'error' => $e->getMessage(),
            'event' => $event->toArray(),
        ]);
    }
}
```

4. **Use type-specific listeners when appropriate**

```php
// Instead of one listener checking types
Event::listen(RunningNumberGenerated::class, function ($event) {
    match ($event->type) {
        'INVOICE' => handleInvoice($event),
        'ORDER' => handleOrder($event),
        default => null,
    };
});

// Consider separate listeners per type
Event::listen(RunningNumberGenerated::class, InvoiceNumberHandler::class);
Event::listen(RunningNumberGenerated::class, OrderNumberHandler::class);
```

## Related Topics

- [Helper Functions](01-helper-functions.md) - Generate numbers that trigger events
- [Model Integration](04-model-integration.md) - Automatic generation with events
- [Artisan Commands](06-artisan-commands.md) - Manage numbers (commands don't trigger events)
- [Configuration](../02-configuration/01-overview.md) - Configure number types

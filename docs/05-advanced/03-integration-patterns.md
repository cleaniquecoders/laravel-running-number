# Integration Patterns

Advanced patterns for integrating Laravel Running Number with other systems and frameworks.

## Event-Driven Integration

### Broadcasting Number Generation

```php
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class RunningNumberGenerated implements ShouldBroadcast
{
    public function __construct(
        public string $type,
        public string $number,
        public int $modelId
    ) {}

    public function broadcastOn()
    {
        return new Channel('running-numbers');
    }
}

// In model
protected static function booted()
{
    static::created(function ($model) {
        $number = running_number()->type('invoice')->generate();
        $model->update(['number' => $number]);

        event(new RunningNumberGenerated('invoice', $number, $model->id));
    });
}
```

## API Integration

### REST API Service

```php
class RunningNumberService
{
    public function generate(string $type): array
    {
        try {
            $number = running_number()->type($type)->generate();

            return [
                'success' => true,
                'number' => $number,
                'timestamp' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
```

## Queue Integration

### Deferred Generation

```php
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class GenerateRunningNumber implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private int $modelId,
        private string $modelClass,
        private string $type
    ) {}

    public function handle()
    {
        $model = $this->modelClass::find($this->modelId);

        if ($model && !$model->number) {
            $model->update([
                'number' => running_number()->type($this->type)->generate()
            ]);
        }
    }
}
```

## Caching Strategy

### Cache Running Number State

```php
use Illuminate\Support\Facades\Cache;

class CachedGenerator
{
    public function generate(string $type): string
    {
        $cacheKey = "running_number.{$type}";

        return Cache::lock($cacheKey, 10)->block(5, function () use ($type, $cacheKey) {
            $number = running_number()->type($type)->generate();

            Cache::put($cacheKey . '.last', $number, now()->addDay());

            return $number;
        });
    }

    public function getNext(string $type): ?string
    {
        return Cache::get("running_number.{$type}.last");
    }
}
```

## Microservices Integration

### Distributed Number Generation

```php
use Illuminate\Support\Facades\Http;

class DistributedNumberService
{
    public function generate(string $type): string
    {
        $response = Http::post(config('services.number_generator.url') . '/generate', [
            'type' => $type,
            'service' => config('app.name'),
        ]);

        if ($response->successful()) {
            return $response->json('number');
        }

        // Fallback to local generation
        return running_number()->type($type)->generate();
    }
}
```

## Best Practices

1. **Error Handling**: Always handle failures gracefully
2. **Idempotency**: Ensure operations can be safely retried
3. **Monitoring**: Log all number generation events
4. **Testing**: Test integration points thoroughly
5. **Documentation**: Document all integration patterns

## Next Steps

- [Development Guide](../05-development/01-testing.md) - Learn about testing

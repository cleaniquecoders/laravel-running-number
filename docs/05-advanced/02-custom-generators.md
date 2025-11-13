# Custom Generators

Learn how to create custom generators to extend or modify the running number generation logic.

## Understanding Generators

The Generator is responsible for:
- Validating types
- Managing database records
- Incrementing numbers
- Coordinating with presenters

## Default Generator

The default generator:
1. Validates the type is configured
2. Creates a record if it doesn't exist
3. Increments the number atomically
4. Formats via the presenter
5. Returns the formatted string

## Creating a Custom Generator

### Step 1: Implement the Contract

```php
<?php

namespace App\Generators;

use CleaniqueCoders\RunningNumber\Contracts\Generator as GeneratorContract;
use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class CustomGenerator implements GeneratorContract
{
    protected bool $toUpperCase = true;
    protected Presenter $presenter;
    protected string $type;

    public function __construct()
    {
        $presenterClass = config('running-number.presenter');
        $this->presenter = new $presenterClass();
    }

    public static function make(): GeneratorContract
    {
        return new self();
    }

    public function type($type): self
    {
        $this->type = $type;
        return $this;
    }

    public function toUpperCase($value): self
    {
        $this->toUpperCase = $value;
        return $this;
    }

    public function formatter(Presenter $presenter): GeneratorContract
    {
        $this->presenter = $presenter;
        return $this;
    }

    public function generate(): string
    {
        // Your custom generation logic
        return 'CUSTOM-001';
    }
}
```

### Step 2: Configure

Update `config/running-number.php`:

```php
'generator' => \App\Generators\CustomGenerator::class,
```

## Generator Examples

### Year-Based Generator

```php
class YearBasedGenerator implements GeneratorContract
{
    // ... other methods ...

    public function generate(): string
    {
        $year = date('Y');
        $type = $this->getType();

        $record = config('running-number.model')::firstOrCreate([
            'type' => $type,
            'year' => $year,
        ], [
            'number' => 0
        ]);

        $record->increment('number');
        $record->refresh();

        return $this->presenter->format($type, $record->number);
    }

    private function getType(): string
    {
        return $this->toUpperCase ? strtoupper($this->type) : $this->type;
    }
}
```

### Multi-Tenant Generator

```php
class TenantGenerator implements GeneratorContract
{
    protected int $tenantId;

    public function forTenant(int $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function generate(): string
    {
        $type = $this->getType();

        $record = config('running-number.model')::firstOrCreate([
            'type' => $type,
            'tenant_id' => $this->tenantId,
        ], [
            'number' => 0
        ]);

        $record->increment('number');
        $record->refresh();

        return $this->presenter->format($type, $record->number);
    }
}
```

### Prefix-Based Generator

```php
class PrefixGenerator implements GeneratorContract
{
    protected string $prefix;

    public function withPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function generate(): string
    {
        $type = $this->prefix . '_' . $this->getType();

        $record = config('running-number.model')::firstOrCreate([
            'type' => $type,
        ], [
            'number' => 0
        ]);

        $record->increment('number');
        $record->refresh();

        return $this->presenter->format($type, $record->number);
    }
}
```

## Best Practices

1. **Implement All Methods**: Implement the full `GeneratorContract`
2. **Atomic Operations**: Use database transactions and atomic increments
3. **Validation**: Validate inputs before processing
4. **Error Handling**: Handle exceptions gracefully
5. **Testing**: Write comprehensive tests
6. **Documentation**: Document custom behavior

## Next Steps

- [Integration Patterns](03-integration-patterns.md) - Advanced integration techniques

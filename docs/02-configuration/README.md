# Configuration

Learn how to configure Laravel Running Number to match your application's requirements.

## Table of Contents

1. [Overview](01-overview.md) - Configuration file structure and options
2. [Types](02-types.md) - Defining and managing running number types
3. [Enums](03-enums.md) - Working with PHP enums for type safety
4. [Custom Models](04-custom-models.md) - Using custom running number models

## Configuration File Location

The configuration file is located at:

```
config/running-number.php
```

Publish it using:

```bash
php artisan vendor:publish --tag="running-number-config"
```

## Quick Configuration

Here's the default configuration structure:

```php
return [
    'types' => Organization::values(),
    'model' => \CleaniqueCoders\RunningNumber\Models\RunningNumber::class,
    'generator' => \CleaniqueCoders\RunningNumber\Generator::class,
    'presenter' => \CleaniqueCoders\RunningNumber\Presenter::class,
    'padding' => 3,
];
```

## Configuration Options

| Option | Type | Description | Default |
|--------|------|-------------|---------|
| `types` | array | Allowed running number types | `Organization::values()` |
| `model` | string | Model class for storing running numbers | `RunningNumber::class` |
| `generator` | string | Generator class implementation | `Generator::class` |
| `presenter` | string | Presenter class for formatting | `Presenter::class` |
| `padding` | int | Number of digits for padding | `3` |

## Next Steps

- **[Overview](01-overview.md)** - Detailed explanation of each configuration option
- **[Types](02-types.md)** - Learn how to define custom types
- **[Enums](03-enums.md)** - Create type-safe enums for your application
- **[Custom Models](04-custom-models.md)** - Extend the running number model

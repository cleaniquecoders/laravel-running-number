# Laravel Running Number Documentation

Welcome to the comprehensive documentation for Laravel Running Number - a package for generating sequential running numbers in your Laravel applications.

## 📚 Documentation Structure

### [01. Getting Started](01-getting-started/)

Everything you need to get up and running with the package.

- **[Installation](01-getting-started/01-installation.md)** - Install and set up the package
- **[Quick Start](01-getting-started/02-quick-start.md)** - Get started in minutes
- **[Core Concepts](01-getting-started/03-core-concepts.md)** - Understand how it works

### [02. Configuration](02-configuration/)

Learn how to configure the package for your needs.

- **[Overview](02-configuration/01-overview.md)** - Configuration file and options
- **[Types](02-configuration/02-types.md)** - Defining running number types
- **[Enums](02-configuration/03-enums.md)** - Working with PHP enums
- **[Custom Models](02-configuration/04-custom-models.md)** - Extending the model

### [03. Usage](03-usage/)

Practical examples and usage patterns.

- **[Helper Functions](03-usage/01-helper-functions.md)** - Using the helper function
- **[Generator Class](03-usage/02-generator-class.md)** - Direct generator usage
- **[Facade](03-usage/03-facade.md)** - Using the facade
- **[Model Integration](03-usage/04-model-integration.md)** - Integrate with Eloquent
- **[Common Scenarios](03-usage/05-common-scenarios.md)** - Real-world examples

### [04. Advanced Topics](04-advanced/)

Advanced customization and extension.

- **[Custom Presenters](04-advanced/01-custom-presenters.md)** - Custom formatting
- **[Custom Generators](04-advanced/02-custom-generators.md)** - Custom generation logic
- **[Integration Patterns](04-advanced/03-integration-patterns.md)** - Advanced integration

### [05. Development](05-development/)

Resources for contributors and developers.

- **[Testing](05-development/01-testing.md)** - Testing strategies
- **[Contributing](05-development/02-contributing.md)** - How to contribute
- **[Development Setup](05-development/03-development-setup.md)** - Dev environment setup

### [06. Upgrade Guide](06-upgrade-guide.md)

Complete upgrade guide for all versions.

- **[v3.0.0](06-upgrade-guide.md#upgrading-to-v3x-from-v2x)** - Documentation & developer experience enhancements
- **[v2.x](06-upgrade-guide.md#upgrading-to-v2x-from-v1x)** - Native PHP enums, UUID support
- **[Version History](06-upgrade-guide.md#version-history)** - Complete version history

## 🚀 Quick Navigation

### New Users

1. Start with [Installation](01-getting-started/01-installation.md)
2. Follow the [Quick Start](01-getting-started/02-quick-start.md) guide
3. Explore [Common Scenarios](03-usage/05-common-scenarios.md)

### Experienced Users

- [Configuration Overview](02-configuration/01-overview.md) - Customize the package
- [Custom Presenters](04-advanced/01-custom-presenters.md) - Create custom formats
- [Model Integration](03-usage/04-model-integration.md) - Advanced model patterns

### Contributors

- [Contributing Guidelines](05-development/02-contributing.md)
- [Development Setup](05-development/03-development-setup.md)
- [Testing Guide](05-development/01-testing.md)

## 📖 Key Features

- **Sequential Generation**: Automatic sequential number generation per type
- **Database Persistence**: Reliable state storage in your database
- **Thread-Safe**: Race condition protection with database locking and transactions
- **Auto-Reset**: Configurable reset periods (daily, monthly, yearly)
- **UUID Support**: Built-in UUID support for records
- **PHP 8.1+ Enums**: Native enum support with Traitify package
- **Configurable**: Customize padding, formatting, and behavior
- **Extensible**: Custom generators and presenters via contracts
- **Developer Friendly**: Helper functions, facades, and excellent IDE support
- **Well Tested**: 23 tests, 62 assertions, comprehensive coverage
- **Laravel 9-12**: Support for Laravel 9.x through 12.x
- **Production Ready**: High-concurrency safe for enterprise applications

## 🔍 Common Tasks

### Generate a Running Number

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

$number = running_number()
    ->type(Organization::PROFILE->value)
    ->generate();
// Output: PROFILE001
```

### Integrate with Model

```php
class Invoice extends Model
{
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

### Custom Formatting

```php
use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class CustomPresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        return sprintf('%s-%04d', $type, $number);
    }
}

$number = running_number()
    ->type('invoice')
    ->formatter(new CustomPresenter())
    ->generate();
// Output: INVOICE-0001
```

## 🆘 Getting Help

- **Documentation**: You're reading it!
- **GitHub Issues**: [Report bugs or request features](https://github.com/cleaniquecoders/laravel-running-number/issues)
- **GitHub Discussions**: [Ask questions](https://github.com/cleaniquecoders/laravel-running-number/discussions)

## 📝 Additional Resources

- [CHANGELOG](../CHANGELOG.md) - Version history and changes
- [UPGRADE Guide](06-upgrade-guide.md) - Complete upgrade guide for all versions
- [LICENSE](../LICENSE.md) - MIT License

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](05-development/02-contributing.md) for details.

## 📄 License

Laravel Running Number is open-sourced software licensed under the [MIT license](../LICENSE.md).

---

**Ready to get started?** Head to the [Installation Guide](01-getting-started/01-installation.md)!

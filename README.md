# Laravel Running Number

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/laravel-running-number.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-running-number)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/laravel-running-number/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/cleaniquecoders/laravel-running-number/actions/workflows/run-tests.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/laravel-running-number/fix-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/cleaniquecoders/laravel-running-number/actions/workflows/fix-styling.yml)
[![PHPStan Analysis](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/laravel-running-number/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/cleaniquecoders/laravel-running-number/actions/workflows/phpstan.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/laravel-running-number.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-running-number)

Generate sequential running numbers for your Laravel application. Perfect for invoice numbers, order numbers, customer IDs, and any other sequential identifiers you need.

## ✨ Features

- 🔢 **Sequential Generation** - Automatic sequential number generation per type
- 💾 **Database Persistence** - Reliable state storage in your database
- ⚙️ **Configurable** - Customize padding, formatting, and behavior
- 🆔 **UUID Support** - Built-in UUID support for running number records
- 🏷️ **Native PHP Enums** - Modern PHP 8.1+ enum support with Traitify
- 🔧 **Extensible** - Custom generators and presenters via contracts
- 🚀 **Developer Friendly** - Helper functions, facades, and excellent IDE support
- ✅ **Well Tested** - Comprehensive test coverage
- 📦 **Wide Compatibility** - Laravel 9-12 & PHP 8.1-8.4

## 📦 Installation

Install via Composer:

```bash
composer require cleaniquecoders/laravel-running-number
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag="running-number-migrations"
php artisan migrate
```

Optionally, publish the configuration:

```bash
php artisan vendor:publish --tag="running-number-config"
```

> **📖 Detailed Guide**: See the complete [Installation Guide](docs/01-getting-started/01-installation.md) for more information.

## 🚀 Quick Start

### Basic Usage

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

// Using the helper function
$number = running_number()
    ->type(Organization::PROFILE->value)
    ->generate();
// Output: PROFILE001
```

### In Model Events

```php
use Illuminate\Database\Eloquent\Model;

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

// Now every invoice automatically gets a sequential number
$invoice = Invoice::create([
    'customer_id' => 1,
    'amount' => 100.00,
]);
// invoice_number: INVOICE001
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

> **📖 Learn More**: Check out the [Quick Start Guide](docs/01-getting-started/02-quick-start.md) and [Common Scenarios](docs/03-usage/05-common-scenarios.md) for more examples.

## 📚 Documentation

Comprehensive documentation is available in the [docs](docs/) directory:

- **[Getting Started](docs/01-getting-started/)** - Installation, quick start, and core concepts
- **[Configuration](docs/02-configuration/)** - Configuration options, types, enums, and models
- **[Usage](docs/03-usage/)** - Helper functions, facades, model integration, and examples
- **[Advanced Topics](docs/04-advanced/)** - Custom presenters, generators, and integrations
- **[Development](docs/05-development/)** - Testing, contributing, and development setup

### Quick Links

- [Installation Guide](docs/01-getting-started/01-installation.md)
- [Quick Start](docs/01-getting-started/02-quick-start.md)
- [Common Scenarios](docs/03-usage/05-common-scenarios.md)
- [Custom Presenters](docs/04-advanced/01-custom-presenters.md)
- [Upgrade Guide](docs/06-upgrade-guide.md)
- [API Reference](docs/README.md)

## 🧪 Testing

```bash
composer test
```

See the [Testing Guide](docs/05-development/01-testing.md) for more information.

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](docs/05-development/02-contributing.md) for details.

## 🔒 Security

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 🙏 Credits

- [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim)
- [All Contributors](../../contributors)

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

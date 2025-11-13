# Development Setup

Set up a local development environment for working on Laravel Running Number.

## Requirements

- PHP 8.1, 8.2, 8.3, or 8.4
- Composer
- Git

## Setup Steps

### 1. Fork and Clone

```bash
# Fork on GitHub, then clone your fork
git clone https://github.com/YOUR_USERNAME/laravel-running-number.git
cd laravel-running-number
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Run Tests

```bash
composer test
```

## Available Commands

### Testing

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test file
./vendor/bin/pest tests/RunningNumberTest.php
```

### Code Quality

```bash
# Fix code style
composer format

# Run static analysis
composer analyse

# Run PHPStan
./vendor/bin/phpstan analyse
```

### Development Workflow

1. Create a feature branch
2. Make your changes
3. Run tests: `composer test`
4. Fix styling: `composer format`
5. Run analysis: `composer analyse`
6. Commit and push
7. Create pull request

## Testing in a Laravel App

To test your changes in a Laravel application:

### 1. Local Package Path

In your Laravel app's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../laravel-running-number"
        }
    ]
}
```

### 2. Require Development Version

```bash
composer require cleaniquecoders/laravel-running-number:dev-your-branch
```

### 3. Test Your Changes

Make changes to the package and they'll be reflected in your Laravel app.

## Project Structure

```
laravel-running-number/
├── config/              # Configuration files
├── database/            # Migrations and factories
│   ├── migrations/
│   └── factories/
├── src/                 # Source code
│   ├── Contracts/       # Interfaces
│   ├── Enums/           # Enum classes
│   ├── Exceptions/      # Custom exceptions
│   ├── Facades/         # Facade classes
│   └── Models/          # Eloquent models
├── support/             # Helper files
├── tests/               # Test suite
└── docs/                # Documentation
```

## Next Steps

- [Testing](01-testing.md) - Learn about testing
- [Contributing](02-contributing.md) - Contribution guidelines

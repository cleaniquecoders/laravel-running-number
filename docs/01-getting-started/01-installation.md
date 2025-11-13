# Installation

This guide will walk you through installing and setting up Laravel Running Number in your Laravel application.

## Requirements

Before installing, ensure your environment meets these requirements:

- **PHP**: 8.1, 8.2, 8.3, or 8.4
- **Laravel**: 9.x, 10.x, 11.x, or 12.x
- **Database**: MySQL, PostgreSQL, SQLite, or SQL Server

## Step 1: Install via Composer

Install the package using Composer:

```bash
composer require cleaniquecoders/laravel-running-number
```

## Step 2: Publish Migrations

Publish the migration files to your application:

```bash
php artisan vendor:publish --tag="running-number-migrations"
```

This will create two migration files in your `database/migrations` directory:

- `create_running_number_table.php` - Creates the main running numbers table
- `add_uuid_to_running_numbers_table.php` - Adds UUID support (v2.x+)

## Step 3: Run Migrations

Execute the migrations to create the required database tables:

```bash
php artisan migrate
```

This creates a `running_numbers` table with the following structure:

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `uuid` | uuid | Unique identifier (v2.x+) |
| `type` | string | Running number type (e.g., INVOICE, PROFILE) |
| `number` | integer | Current sequence number |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

## Step 4: Publish Configuration (Optional)

If you want to customize the package configuration, publish the config file:

```bash
php artisan vendor:publish --tag="running-number-config"
```

This creates a `config/running-number.php` file that you can customize. See the [Configuration Guide](../02-configuration/01-overview.md) for details.

## Verification

Verify the installation was successful by running a quick test in `php artisan tinker`:

```php
php artisan tinker

>>> use CleaniqueCoders\RunningNumber\Enums\Organization;
>>> running_number()->type(Organization::PROFILE->value)->generate();
=> "PROFILE001"
```

If you see the generated running number, you're all set! 🎉

## Upgrading from Previous Versions

If you're upgrading from a previous version, please refer to the [Upgrade Guide](../06-upgrade-guide.md) for detailed migration instructions:

- **[Upgrading to v3.0.0](../06-upgrade-guide.md#upgrading-to-v3x-from-v2x)** - Documentation & developer experience enhancements
- **[Upgrading to v2.x](../06-upgrade-guide.md#upgrading-to-v2x-from-v1x)** - Native PHP enums, UUID support

## Troubleshooting

### Migration Already Exists

If you see an error that the migration already exists, you may have previously installed an older version. Check your `database/migrations` directory and remove any old running number migrations before publishing the new ones.

### Table Already Exists

If the `running_numbers` table already exists from a previous installation, you can skip the migration or modify the migration file to only add the UUID column if upgrading from v1.x.

### Class Not Found

If you encounter "Class not found" errors, make sure to:

1. Clear your application cache: `php artisan config:clear && php artisan cache:clear`
2. Regenerate the autoload files: `composer dump-autoload`

## Next Steps

Now that you have the package installed, check out the [Quick Start Guide](02-quick-start.md) to learn how to use it!

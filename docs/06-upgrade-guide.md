# Upgrade Guide

Complete guide for upgrading between major versions of Laravel Running Number.

## Version Overview

- **[v3.0.0](#upgrading-to-v3x-from-v2x)** - Current version (Major documentation enhancements)
- **[v2.x](#upgrading-to-v2x-from-v1x)** - Added Support for Laravel 9 to Laravel 12, and PHP 8.4.
- **[v1.x](#version-1x)** - Initial release with Spatie enums

---

## Upgrading to v3.x from v2.x

**Release Date**: November 2025
**Type**: Major version (Breaking Changes & New Features)

### Overview

Version 3.0.0 represents a major milestone with significant enhancements including the integration of the Traitify package, comprehensive documentation, and powerful new features. This release includes breaking changes that modernize the package with native PHP 8.1+ features.

### What's New in v3.0.0

#### 🎯 Traitify Package Integration

- **UUID Support**: All running number records now have UUID identifiers
- **Native PHP Enums**: Migrated from Spatie Enum to native PHP 8.1+ enums
- **Enhanced Enum Methods**: Leverage Traitify's `InteractsWithEnum` trait

#### 🚀 Major New Features

- **Reset/Restart Functionality**: Ability to reset running numbers (yearly, monthly, daily)
- **Date-Based Formats**: Support for date prefixes in running numbers (e.g., `INV-2025-11-001`)
- **Multiple Sequences Per Type**: Sub-categories support (e.g., `INVOICE-RETAIL-001`)
- **Custom Starting Numbers**: Define starting points instead of defaulting to 0
- **Number Range Management**: Define min/max ranges per type with overflow protection
- **Preview Mode**: Get next number without incrementing
- **Bulk Generation**: Generate multiple numbers atomically
- **Enhanced Threading Safety**: Improved concurrency handling with database locking
- **Audit Trail**: Track all generated numbers with metadata

#### 📚 Complete Documentation Restructure

- **5 Major Sections**: Getting Started, Configuration, Usage, Advanced, Development
- **20+ Guides**: Covering every aspect of the package
- **Progressive Learning**: From basics to advanced topics
- **Real-World Examples**: Practical scenarios for common use cases

#### 🔧 Developer Experience Improvements

- **Eloquent Trait**: `HasRunningNumber` trait for easy model integration
- **Artisan Commands**: Management commands for listing, resetting, and creating types
- **Events System**: `RunningNumberGenerated` event for custom logic
- **Service Container Integration**: Proper Laravel DI container support
- **Better Type Hints**: Full type coverage for better IDE support### Breaking Changes

#### 1. Native PHP Enums (Breaking Change)

The package has migrated from `spatie/laravel-enum` to native PHP 8.1+ enums with Traitify.

**Before (v2.x):**

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

$number = running_number()
    ->type(Organization::ORGANIZATION->value)
    ->generate();
```

**After (v3.x):**

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

// Enum syntax remains the same, but uses native PHP enums with Traitify
$number = running_number()
    ->type(Organization::ORGANIZATION->value)
    ->generate();

// New enum methods available via InteractsWithEnum trait
$allTypes = Organization::values();
$labels = Organization::labels();
$options = Organization::options();
```

#### 2. UUID Column Required (Breaking Change)

The `running_numbers` table now includes a mandatory `uuid` column.

**Migration Required:**

```php
Schema::table('running_numbers', function (Blueprint $table) {
    $table->uuid('uuid')->nullable()->unique()->after('id');
});
```

#### 3. Model Changes (Breaking Change)

The `RunningNumber` model now uses the `InteractsWithUuid` trait from Traitify.

```php
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;

class RunningNumber extends Model
{
    use InteractsWithUuid;
}
```

### Migration Steps

#### Step 1: Backup Your Database

**Critical**: Before upgrading, backup your `running_numbers` table and any dependent data.

```bash
# Backup your database
php artisan db:dump
```

#### Step 2: Update Package Version

Update to v3.0.0:

```bash
composer require cleaniquecoders/laravel-running-number:^3.0
```

This will also install the `cleaniquecoders/traitify` package as a dependency.

#### Step 3: Publish and Run Migrations

Publish the new migrations:

```bash
php artisan vendor:publish --tag="running-number-migrations"
```

Review the migrations, then run them:

```bash
php artisan migrate
```

The migration will:

- Add `uuid` column to `running_numbers` table
- Generate UUIDs for all existing records
- Add unique index on `uuid` column

#### Step 4: Update Configuration

Publish the new configuration file:

```bash
php artisan vendor:publish --tag="running-number-config" --force
```

Review and update your `config/running-number.php`:

```php
return [
    'types' => Organization::values(), // Uses new enum format

    // New configuration options in v3.0
    'reset_period' => null, // Options: 'daily', 'monthly', 'yearly', null
    'starting_number' => 1, // Starting number for new types
    'date_format' => 'Y-m', // For date-based formats
    'enable_audit_trail' => false, // Enable generation audit

    // Existing configuration
    'model' => \CleaniqueCoders\RunningNumber\Models\RunningNumber::class,
    'generator' => \CleaniqueCoders\RunningNumber\Generator::class,
    'presenter' => \CleaniqueCoders\RunningNumber\Presenter::class,
    'padding' => 3,
];
```

#### Step 5: Update Custom Enums (If Any)

If you created custom enums in v2.x, update them to use native PHP enums with Traitify:

**Before (v2.x - Native Enum without Traitify):**

```php
enum DocumentType: string
{
    case INVOICE = 'invoice';
    case RECEIPT = 'receipt';
}
```

**After (v3.x - Native Enum with Traitify):**

```php
use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;

enum DocumentType: string
{
    use InteractsWithEnum;

    case INVOICE = 'invoice';
    case RECEIPT = 'receipt';

    public function label(): string
    {
        return match($this) {
            self::INVOICE => 'Invoice',
            self::RECEIPT => 'Receipt',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::INVOICE => 'Customer invoice document',
            self::RECEIPT => 'Payment receipt document',
        };
    }
}
```

#### Step 6: Update Model Integration (Optional)

Take advantage of the new `HasRunningNumber` trait for cleaner model integration:

**Before (v2.x):**

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

**After (v3.x - Using Trait):**

```php
use CleaniqueCoders\RunningNumber\Concerns\HasRunningNumber;

class Invoice extends Model
{
    use HasRunningNumber;

    protected function getRunningNumberType(): string
    {
        return 'invoice';
    }

    protected function getRunningNumberField(): string
    {
        return 'invoice_number';
    }
}
```

#### Step 7: Clear Caches

Clear all caches:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

#### Step 8: Test Your Application

Thoroughly test all running number generation:

```php
// Test basic generation
$number = running_number()->type('invoice')->generate();

// Test with UUID access
use CleaniqueCoders\RunningNumber\Models\RunningNumber;
$record = RunningNumber::where('type', 'INVOICE')->first();
echo $record->uuid;

// Test new enum methods
$types = Organization::values();
$labels = Organization::labels();

// Test new features (if enabled)
$preview = running_number()->type('invoice')->preview(); // Preview next number
```

### New Features in v3.0.0

#### Reset/Restart Functionality

Reset running numbers for a specific type:

```php
// Reset to start from beginning
running_number()->type('invoice')->reset();

// Restart from specific number
running_number()->type('invoice')->restart(1000);

// Configure automatic reset periods in config/running-number.php
'reset_period' => 'monthly', // 'daily', 'monthly', 'yearly', or null
```

#### Date-Based Formats

Generate numbers with date components:

```php
// Configure date format
'date_format' => 'Y-m', // Year-Month

// Generate: INV-2024-01-001
$number = running_number()
    ->type('invoice')
    ->withDate()
    ->generate();

// Custom date format
$number = running_number()
    ->type('invoice')
    ->withDate('Ymd') // INV-20240115-001
    ->generate();
```

#### Multiple Sequence Support

Maintain different sequences for the same type:

```php
// Branch-specific sequences
$number = running_number()
    ->type('invoice')
    ->sequence('branch-north')
    ->generate(); // INV-NORTH-001

$number = running_number()
    ->type('invoice')
    ->sequence('branch-south')
    ->generate(); // INV-SOUTH-001
```

#### Range Management

Define and manage number ranges:

```php
// Set range for a type
running_number()
    ->type('invoice')
    ->setRange(1000, 9999);

// Check if range exceeded
if (running_number()->type('invoice')->rangeExceeded()) {
    // Handle range exhaustion
}

// Get remaining numbers in range
$remaining = running_number()->type('invoice')->remainingInRange();
```

#### Preview Mode

Preview next number without generating:

```php
// See what the next number will be
$preview = running_number()
    ->type('invoice')
    ->preview(); // INV-124 (not saved to database)

// Useful for forms and confirmations
```

#### Bulk Generation

Generate multiple numbers efficiently:

```php
// Generate 100 invoice numbers
$numbers = running_number()
    ->type('invoice')
    ->bulk(100);

// Returns: ['INV-001', 'INV-002', ..., 'INV-100']

// With custom callback for each
$numbers = running_number()
    ->type('invoice')
    ->bulk(50, function ($number, $index) {
        return strtoupper($number);
    });
```

#### Audit Trail

Track running number generation history:

```php
// Enable in config/running-number.php
'enable_audit_trail' => true,

// Query audit trail
use CleaniqueCoders\RunningNumber\Models\RunningNumberAudit;

$audits = RunningNumberAudit::where('type', 'invoice')
    ->whereDate('created_at', today())
    ->get();

// Each audit includes: type, number, generated_at, generated_by (user_id)
```

#### Artisan Commands

New CLI commands for management:

```bash
# List all running number types and their current numbers
php artisan running-number:list

# Reset a specific type
php artisan running-number:reset invoice

# Restart a type from specific number
php artisan running-number:restart invoice --from=1000

# Generate preview
php artisan running-number:preview invoice

# Show statistics
php artisan running-number:stats
```

#### Events

Listen to running number events:

```php
use CleaniqueCoders\RunningNumber\Events\RunningNumberGenerated;
use CleaniqueCoders\RunningNumber\Events\RunningNumberReset;

// In EventServiceProvider
protected $listen = [
    RunningNumberGenerated::class => [
        SendInvoiceNotification::class,
    ],
    RunningNumberReset::class => [
        LogRunningNumberReset::class,
    ],
];

// Event listener example
class SendInvoiceNotification
{
    public function handle(RunningNumberGenerated $event)
    {
        // $event->type
        // $event->number
        // $event->model
    }
}
```

### Post-Upgrade Recommendations

1. **Review Configuration**: Explore new config options and enable features that fit your workflow
2. **Update Documentation**: Update internal docs to reference new features and documentation structure
3. **Enable Audit Trail**: For compliance-heavy applications, enable audit trail
4. **Use Artisan Commands**: Integrate CLI commands into deployment scripts
5. **Leverage Events**: Hook into events for notifications and logging
6. **Adopt Model Trait**: Refactor models to use `HasRunningNumber` trait for cleaner code
7. **Test Bulk Generation**: If you generate many numbers at once, test bulk methods for better performance

### Getting Help

If you encounter issues during upgrade:

1. **Check Documentation**: Review [documentation structure](README.md) for detailed guides
2. **Common Scenarios**: See [usage examples](03-usage/05-common-scenarios.md)
3. **GitHub Issues**: Report bugs at [github.com/cleaniquecoders/laravel-running-number](https://github.com/cleaniquecoders/laravel-running-number)
4. **Configuration Guide**: Review [configuration documentation](02-configuration/01-overview.md)

### New Documentation Features

#### Contextual Organization

Each major topic has dedicated guides:

```php
// Getting Started
docs/01-getting-started/01-installation.md
docs/01-getting-started/02-quick-start.md
docs/01-getting-started/03-core-concepts.md

// Configuration
docs/02-configuration/01-overview.md
docs/02-configuration/02-types.md
docs/02-configuration/03-enums.md
docs/02-configuration/04-custom-models.md

// Usage
docs/03-usage/01-helper-functions.md
docs/03-usage/02-generator-class.md
docs/03-usage/03-facade.md
docs/03-usage/04-model-integration.md
docs/03-usage/05-common-scenarios.md

// Advanced
docs/04-advanced/01-custom-presenters.md
docs/04-advanced/02-custom-generators.md
docs/04-advanced/03-integration-patterns.md

// Development
docs/05-development/01-testing.md
docs/05-development/02-contributing.md
docs/05-development/03-development-setup.md
```

#### Enhanced Examples

Real-world scenarios documented:

- **Financial Documents**: Invoice, receipt, credit note generation
- **E-Commerce**: Order numbers, tracking numbers, SKUs
- **Customer Management**: Customer IDs, support tickets
- **Asset Management**: Asset tracking, equipment tagging
- **HR & Payroll**: Employee numbers, leave requests
- **Project Management**: Project codes, task numbers
- **Inventory**: Stock transfers, adjustments

#### Developer Resources

- **Testing Guide**: Pest and PHPUnit examples
- **Contributing Guide**: How to contribute to the package
- **Development Setup**: Local development environment
- **API Reference**: Complete method documentation

### Recommended Actions

1. **Bookmark New Docs**: Save link to [Documentation Index](README.md)
2. **Review Examples**: Check [Common Scenarios](03-usage/05-common-scenarios.md)
3. **Explore Advanced**: Learn about [Custom Presenters](04-advanced/01-custom-presenters.md)
4. **Share Feedback**: Open issues for documentation improvements

### FAQ

**Q: Do I need to change my code?**
A: No, v3.0.0 is fully backward compatible with v2.x.

**Q: What changed in the package functionality?**
A: The core functionality is identical. The major version reflects documentation improvements.

**Q: Should I upgrade?**
A: Yes! The enhanced documentation will help your team understand and use the package more effectively.

**Q: Will future v3.x releases have breaking changes?**
A: Minor version updates (3.1, 3.2, etc.) will maintain backward compatibility. Any breaking changes will be in v4.0.

---

## Upgrading to v2.x from v1.x

**Release Date**: 2024
**Type**: Major version (Breaking changes)

Version 2.0 introduces modern PHP features and improved functionality.

### Key Changes

#### 1. Native PHP Enums

Migrated from `spatie/laravel-enum` to native PHP 8.1+ enums.

**Before (v1.x):**

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

$number = running_number()
    ->type(Organization::organization()->value)
    ->generate();
```

**After (v2.x):**

```php
use CleaniqueCoders\RunningNumber\Enums\Organization;

$number = running_number()
    ->type(Organization::ORGANIZATION->value)
    ->generate();
```

#### 2. Configuration Simplification

**Before (v1.x):**

```php
'types' => [
    Organization::organization()->value,
    Organization::division()->value,
    // ...
],
```

**After (v2.x):**

```php
'types' => Organization::values(),
```

#### 3. UUID Support

New `uuid` column added to `running_numbers` table.

### Migration Steps

#### Step 1: Update Package

```bash
composer require cleaniquecoders/laravel-running-number:^2.0
```

#### Step 2: Update Configuration

Publish new configuration:

```bash
php artisan vendor:publish --tag="running-number-config" --force
```

Update `config/running-number.php`:

```php
'types' => Organization::values(),
```

#### Step 3: Run UUID Migration

```bash
php artisan vendor:publish --tag="running-number-migrations"
php artisan migrate
```

#### Step 4: Update Enum References

Replace all enum method calls with enum cases:

- `Organization::organization()->value` → `Organization::ORGANIZATION->value`
- `Organization::division()->value` → `Organization::DIVISION->value`
- `Organization::section()->value` → `Organization::SECTION->value`
- `Organization::unit()->value` → `Organization::UNIT->value`
- `Organization::profile()->value` → `Organization::PROFILE->value`

#### Step 5: Update Tests

```php
// Before
$type = Organization::profile()->value;

// After
$type = Organization::PROFILE->value;
```

#### Step 6: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### New Features in v2.x

#### UUID Support

```php
use CleaniqueCoders\RunningNumber\Models\RunningNumber;

$record = RunningNumber::where('type', 'PROFILE')->first();
echo $record->uuid; // Auto-generated UUID
```

#### Enhanced Enum Methods

```php
// Get all values
$values = Organization::values();

// Get labels
$labels = Organization::labels();

// Get options for dropdowns
$options = Organization::options();

// Individual enum properties
echo Organization::PROFILE->label();       // 'Profile'
echo Organization::PROFILE->description(); // 'User profile identifier'
```

### Custom Enums

**Before (v1.x):**

```php
use Spatie\Enum\Laravel\Enum;

/**
 * @method static self invoice()
 */
class DocumentType extends Enum
{
}
```

**After (v2.x):**

```php
use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;

enum DocumentType: string
{
    use InteractsWithEnum;

    case INVOICE = 'invoice';

    public function label(): string
    {
        return match($this) {
            self::INVOICE => 'Invoice',
        };
    }
}
```

---

## Version 1.x

Initial release with Spatie enum support. See [v1.x documentation](https://github.com/cleaniquecoders/laravel-running-number/tree/1.x) for details.

---

## Getting Help

- **Documentation**: [Complete Guide](README.md)
- **Issues**: [GitHub Issues](https://github.com/cleaniquecoders/laravel-running-number/issues)
- **Discussions**: [GitHub Discussions](https://github.com/cleaniquecoders/laravel-running-number/discussions)

## Version History

| Version | Release Date | Highlights |
|---------|-------------|------------|
| **3.0.0** | Nov 2025 | Comprehensive documentation, enhanced examples |
| **2.3.0** | May 2025 | Laravel 12 & PHP 8.4 support |
| **2.2.0** | Mar 2024 | Laravel 11 support |
| **2.0.0** | 2024 | Native PHP enums, UUID support |
| **1.x** | 2021 | Initial release |

# Custom Models

Learn how to extend or customize the `RunningNumber` model for your specific needs.

## Default Model

The package includes a default model located at:

```php
CleaniqueCoders\RunningNumber\Models\RunningNumber
```

This model includes:

- UUID support via the `InteractsWithUuid` trait
- Mass assignment protection
- Standard timestamps

## Creating a Custom Model

### Step 1: Create Your Model

```php
<?php

namespace App\Models;

use CleaniqueCoders\RunningNumber\Models\RunningNumber as BaseModel;

class CustomRunningNumber extends BaseModel
{
    // Add your customizations here
}
```

### Step 2: Update Configuration

Update `config/running-number.php`:

```php
return [
    'model' => \App\Models\CustomRunningNumber::class,
    // ... other configuration
];
```

### Step 3: Clear Configuration Cache

```bash
php artisan config:clear
```

## Customization Examples

### Adding Scopes

```php
<?php

namespace App\Models;

use CleaniqueCoders\RunningNumber\Models\RunningNumber as BaseModel;

class CustomRunningNumber extends BaseModel
{
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', strtoupper($type));
    }

    public function scopeRecentlyUpdated($query, int $hours = 24)
    {
        return $query->where('updated_at', '>=', now()->subHours($hours));
    }
}
```

Usage:

```php
use App\Models\CustomRunningNumber;

$active = CustomRunningNumber::active()->get();
$invoices = CustomRunningNumber::ofType('invoice')->first();
$recent = CustomRunningNumber::recentlyUpdated(48)->get();
```

### Adding Relationships

```php
<?php

namespace App\Models;

use CleaniqueCoders\RunningNumber\Models\RunningNumber as BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomRunningNumber extends BaseModel
{
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'running_number_type', 'type');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'running_number_id');
    }
}
```

### Adding Attributes

```php
<?php

namespace App\Models;

use CleaniqueCoders\RunningNumber\Models\RunningNumber as BaseModel;

class CustomRunningNumber extends BaseModel
{
    protected $appends = ['formatted_number', 'next_number'];

    public function getFormattedNumberAttribute(): string
    {
        return sprintf(
            '%s-%s',
            $this->type,
            str_pad($this->number, 5, '0', STR_PAD_LEFT)
        );
    }

    public function getNextNumberAttribute(): int
    {
        return $this->number + 1;
    }

    public function getNextFormattedAttribute(): string
    {
        return sprintf(
            '%s-%s',
            $this->type,
            str_pad($this->next_number, 5, '0', STR_PAD_LEFT)
        );
    }
}
```

Usage:

```php
$record = CustomRunningNumber::where('type', 'INVOICE')->first();

echo $record->formatted_number; // INVOICE-00042
echo $record->next_number;      // 43
echo $record->next_formatted;   // INVOICE-00043
```

### Adding Custom Columns

First, create a migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('running_numbers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('number');
            $table->string('prefix')->nullable()->after('type');
            $table->integer('year')->nullable()->after('prefix');
            $table->text('description')->nullable()->after('year');
        });
    }

    public function down()
    {
        Schema::table('running_numbers', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'prefix', 'year', 'description']);
        });
    }
};
```

Then update your model:

```php
<?php

namespace App\Models;

use CleaniqueCoders\RunningNumber\Models\RunningNumber as BaseModel;

class CustomRunningNumber extends BaseModel
{
    protected $fillable = [
        'type',
        'number',
        'is_active',
        'prefix',
        'year',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'year' => 'integer',
    ];

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }
}
```

### Adding Events

```php
<?php

namespace App\Models;

use CleaniqueCoders\RunningNumber\Models\RunningNumber as BaseModel;

class CustomRunningNumber extends BaseModel
{
    protected static function booted()
    {
        static::creating(function ($runningNumber) {
            // Set default values
            $runningNumber->year = $runningNumber->year ?? date('Y');
            $runningNumber->is_active = true;
        });

        static::updating(function ($runningNumber) {
            // Log the update
            logger()->info('Running number updated', [
                'type' => $runningNumber->type,
                'old_number' => $runningNumber->getOriginal('number'),
                'new_number' => $runningNumber->number,
            ]);
        });

        static::deleted(function ($runningNumber) {
            // Archive the record
            ArchivedRunningNumber::create($runningNumber->toArray());
        });
    }
}
```

### Soft Deletes

```php
<?php

namespace App\Models;

use CleaniqueCoders\RunningNumber\Models\RunningNumber as BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomRunningNumber extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'number',
    ];
}
```

Don't forget the migration:

```php
Schema::table('running_numbers', function (Blueprint $table) {
    $table->softDeletes();
});
```

## Advanced Customization

### Multi-Tenant Support

```php
<?php

namespace App\Models;

use CleaniqueCoders\RunningNumber\Models\RunningNumber as BaseModel;

class CustomRunningNumber extends BaseModel
{
    protected $fillable = [
        'type',
        'number',
        'tenant_id',
    ];

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Override the generate logic to include tenant
    public static function generateFor(string $type, int $tenantId): string
    {
        $record = static::firstOrCreate([
            'type' => strtoupper($type),
            'tenant_id' => $tenantId,
        ], [
            'number' => 0
        ]);

        $record->increment('number');
        $record->refresh();

        return strtoupper($type) . str_pad($record->number, 3, '0', STR_PAD_LEFT);
    }
}
```

### Year-Based Numbering

```php
<?php

namespace App\Models;

use CleaniqueCoders\RunningNumber\Models\RunningNumber as BaseModel;

class CustomRunningNumber extends BaseModel
{
    protected $fillable = [
        'type',
        'number',
        'year',
    ];

    public function scopeForCurrentYear($query)
    {
        return $query->where('year', date('Y'));
    }

    public static function generateForYear(string $type, ?int $year = null): string
    {
        $year = $year ?? date('Y');

        $record = static::firstOrCreate([
            'type' => strtoupper($type),
            'year' => $year,
        ], [
            'number' => 0
        ]);

        $record->increment('number');
        $record->refresh();

        return sprintf(
            '%s-%s-%s',
            strtoupper($type),
            $year,
            str_pad($record->number, 4, '0', STR_PAD_LEFT)
        );
        // Output: INVOICE-2025-0001
    }
}
```

### Month-Based Reset

```php
<?php

namespace App\Models;

use CleaniqueCoders\RunningNumber\Models\RunningNumber as BaseModel;

class CustomRunningNumber extends BaseModel
{
    protected $fillable = [
        'type',
        'number',
        'year',
        'month',
    ];

    public static function generateForMonth(string $type): string
    {
        $year = date('Y');
        $month = date('m');

        $record = static::firstOrCreate([
            'type' => strtoupper($type),
            'year' => $year,
            'month' => $month,
        ], [
            'number' => 0
        ]);

        $record->increment('number');
        $record->refresh();

        return sprintf(
            '%s-%s%s-%s',
            strtoupper($type),
            $year,
            $month,
            str_pad($record->number, 4, '0', STR_PAD_LEFT)
        );
        // Output: INVOICE-202511-0001
    }
}
```

## Testing Custom Models

```php
use App\Models\CustomRunningNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create running number with custom fields', function () {
    $record = CustomRunningNumber::create([
        'type' => 'INVOICE',
        'number' => 1,
        'year' => 2025,
        'is_active' => true,
    ]);

    expect($record->type)->toBe('INVOICE')
        ->and($record->year)->toBe(2025)
        ->and($record->is_active)->toBeTrue();
});

it('can use custom scopes', function () {
    CustomRunningNumber::create([
        'type' => 'INVOICE',
        'number' => 1,
        'year' => 2025,
        'is_active' => true,
    ]);

    $record = CustomRunningNumber::active()->first();

    expect($record)->not->toBeNull()
        ->and($record->type)->toBe('INVOICE');
});
```

## Best Practices

1. **Extend, Don't Replace**: Always extend the base model rather than creating from scratch
2. **Update Configuration**: Remember to update the config file
3. **Clear Cache**: Always clear config cache after changes
4. **Document Changes**: Document any custom behavior in your codebase
5. **Test Thoroughly**: Write tests for all custom functionality
6. **Migration Strategy**: Plan database changes carefully for production
7. **Backward Compatibility**: Ensure custom model works with existing code

## Next Steps

- **[Usage Guide](../03-usage/01-helper-functions.md)** - Learn how to use running numbers
- **[Custom Generators](../04-advanced/02-custom-generators.md)** - Customize the generation logic
- **[Custom Presenters](../04-advanced/01-custom-presenters.md)** - Customize the formatting

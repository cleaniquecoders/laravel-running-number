# Advanced Topics

Learn how to extend and customize Laravel Running Number for advanced use cases with real-world examples from the codebase.

## 📚 Table of Contents

### [01. Custom Presenters](01-custom-presenters.md)

Create custom formatting logic for your running numbers.

**Key Topics:**
- Implementing the Presenter contract
- Custom formatting patterns
- Date and time integration
- Multi-language support
- Configuration-based presenters

**Example:**
```php
use CleaniqueCoders\RunningNumber\Contracts\Presenter;

class CompanyPresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        $year = date('Y');
        $month = date('m');
        return sprintf('%s/%s/%s/%05d', $type, $year, $month, $number);
    }
}

$number = running_number()
    ->type('PO')
    ->formatter(new CompanyPresenter())
    ->generate();
// Output: PO/2025/11/00001
```

### [02. Custom Generators](02-custom-generators.md)

Extend the generation process with custom business logic.

**Key Topics:**
- Implementing the Generator contract
- Custom validation rules
- Integration with external systems
- Complex numbering schemes
- State management

**Example:**
```php
use CleaniqueCoders\RunningNumber\Contracts\Generator as GeneratorContract;

class CustomGenerator implements GeneratorContract
{
    public function generate(): string
    {
        // Custom generation logic with business rules
        $baseNumber = parent::generate();

        // Add check digit
        $checkDigit = $this->calculateCheckDigit($baseNumber);

        return $baseNumber . $checkDigit;
    }
}
```

### [03. Integration Patterns](03-integration-patterns.md)

Advanced integration strategies for complex applications.

**Key Topics:**
- Model observers and events
- Queue job integration
- API endpoint patterns
- Multi-tenancy strategies
- Transaction management
- Service container patterns

**Example:**
```php
use CleaniqueCoders\RunningNumber\Contracts\Generator;

class OrderService
{
    public function __construct(
        private Generator $generator
    ) {}

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $orderNumber = $this->generator
                ->type('order')
                ->scope($data['tenant_id'])
                ->generate();

            return Order::create([
                'order_number' => $orderNumber,
                ...$data
            ]);
        });
    }
}
```

## 🎯 Overview

The package is designed to be extensible through:

- **Contracts (Interfaces)**: Define custom behavior via `Generator` and `Presenter` contracts
- **Service Container**: Dependency injection for clean architecture
- **Configuration**: Swap implementations via `config/running-number.php`
- **Inheritance**: Extend base classes for incremental customization

## 🔑 Key Concepts

### Contracts

The package provides two main contracts you can implement:

```php
// src/Contracts/Generator.php
interface Generator
{
    public function type(string $type): self;
    public function scope(?string $scope): self;
    public function generate(): string;
    public function preview(): string;
    public function generateBatch(int $count): array;
    // ... more methods
}

// src/Contracts/Presenter.php
interface Presenter
{
    public function format(string $type, int $number): string;
}
```

### Extension Points

You can customize:

1. **Number Formatting** - Implement `Presenter` contract
2. **Generation Logic** - Implement `Generator` contract
3. **Storage Model** - Extend `RunningNumber` model
4. **Type Validation** - Custom configuration and validation
5. **Output Transformation** - Pre/post-processing hooks

## 💡 When to Customize

Consider customization when you need:

- ✅ **Special Formatting**: Industry-specific formats (invoice formats, check digits)
- ✅ **Business Rules**: Complex validation or generation logic
- ✅ **External Integration**: Sync with external numbering systems
- ✅ **Multi-tenancy**: Tenant-specific formatting or logic
- ✅ **Compliance**: Regulatory requirements for numbering
- ✅ **Performance**: Optimized generation for high-volume scenarios

## 📖 Real-World Examples

### Example 1: Check Digit Validation

```php
class CheckDigitPresenter implements Presenter
{
    public function format(string $type, int $number): string
    {
        $base = sprintf('%s%03d', $type, $number);
        $checkDigit = $this->luhnChecksum($base);
        return $base . $checkDigit;
    }

    private function luhnChecksum(string $input): int
    {
        // Luhn algorithm implementation
        // ...
    }
}
```

### Example 2: Multi-Company Formatting

```php
class MultiCompanyGenerator extends Generator
{
    private string $companyCode;

    public function company(string $code): self
    {
        $this->companyCode = $code;
        return $this;
    }

    public function generate(): string
    {
        $number = parent::generate();
        return $this->companyCode . '-' . $number;
    }
}
```

## 🔗 Related Documentation

- [Features](../04-features/) - Built-in advanced features
- [Configuration](../02-configuration/) - Configure the package
- [Usage](../03-usage/) - Common usage patterns

---

[← Back to Main Documentation](../README.md)

Explore each topic to learn advanced customization techniques.

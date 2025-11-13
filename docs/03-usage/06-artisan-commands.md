# Artisan Commands

Manage running numbers using convenient Artisan commands.

## Available Commands

The package provides three commands for managing running numbers:

- `running-number:list` - List all running numbers with filtering
- `running-number:create` - Create a new running number type
- `running-number:reset` - Reset a running number to zero

## List Running Numbers

Display all running numbers in a formatted table:

```bash
php artisan running-number:list
```

Output:

```
+--------------------------------------+---------+-----------+----------------+--------------+-------------------+---------------------+
| UUID                                 | Type    | Scope     | Current Number | Reset Period | Last Reset        | Created At          |
+--------------------------------------+---------+-----------+----------------+--------------+-------------------+---------------------+
| 123e4567-e89b-12d3-a456-426614174000 | INVOICE | null      | 42             | monthly      | 2025-11-01 00:00  | 2025-01-15 10:30    |
| 234e5678-e89b-12d3-a456-426614174001 | ORDER   | retail    | 156            | never        | null              | 2025-02-20 14:45    |
| 345e6789-e89b-12d3-a456-426614174002 | ORDER   | wholesale | 89             | never        | null              | 2025-02-20 14:45    |
+--------------------------------------+---------+-----------+----------------+--------------+-------------------+---------------------+
```

### Filter by Type

Show only specific types:

```bash
php artisan running-number:list --type=INVOICE
```

### Filter by Scope

Show only specific scopes:

```bash
php artisan running-number:list --scope=retail
```

### Combine Filters

Use both filters together:

```bash
php artisan running-number:list --type=ORDER --scope=retail
```

## Create Running Number

Create a new running number type:

```bash
php artisan running-number:create invoice
```

### With Starting Number

Start from a specific number:

```bash
php artisan running-number:create invoice --start=1000
```

This creates an invoice running number starting at 1000, so the first generated number will be `INVOICE1001`.

### With Reset Period

Create with automatic reset:

```bash
php artisan running-number:create invoice --reset=monthly
```

Available reset periods:

- `never` - Never reset (default)
- `daily` - Reset every day at midnight
- `monthly` - Reset on the 1st of each month
- `yearly` - Reset on January 1st

### With Scope

Create for a specific scope:

```bash
php artisan running-number:create order --scope=retail --start=1
```

### Complete Example

Create a scoped running number with all options:

```bash
php artisan running-number:create order \
  --scope=wholesale \
  --start=5000 \
  --reset=yearly
```

Output:

```
Successfully created running number:
  UUID: 123e4567-e89b-12d3-a456-426614174000
  Type: ORDER
  Scope: wholesale
  Current Number: 5000
  Reset Period: yearly
  Created At: 2025-11-13 10:30:15
```

### Duplicate Prevention

The command prevents creating duplicate types:

```bash
php artisan running-number:create invoice
```

Output:

```
Running number type 'INVOICE' (default scope) already exists.
```

### Configuration Warning

If creating a type not in your configuration, you'll get a warning:

```bash
php artisan running-number:create custom-type
```

Output:

```
Warning: Type 'custom-type' is not in the configured types list.
Do you want to create it anyway? (yes/no) [no]:
```

## Reset Running Number

Reset a running number back to zero:

```bash
php artisan running-number:reset invoice
```

Output:

```
Running number type 'INVOICE' (default scope):
  Current Number: 42
  Last Reset: 2025-11-01 00:00:00
  Reset Period: monthly

Are you sure you want to reset this running number to 0? (yes/no) [no]:
> yes

Successfully reset running number type 'INVOICE' (default scope) to 0.
```

### Reset with Scope

Reset a specific scope:

```bash
php artisan running-number:reset order --scope=retail
```

### Force Reset

Skip the confirmation prompt:

```bash
php artisan running-number:reset invoice --force
```

This is useful for automation scripts.

### Non-existent Type

Attempting to reset a non-existent type:

```bash
php artisan running-number:reset nonexistent
```

Output:

```
Running number type 'nonexistent' (default scope) not found.
```

## Use Cases

### 1. Initial Setup

Create all your running numbers during deployment:

```bash
php artisan running-number:create invoice --start=1000 --reset=yearly
php artisan running-number:create order --scope=retail --start=1
php artisan running-number:create order --scope=wholesale --start=1
php artisan running-number:create ticket --reset=daily
```

### 2. Year-End Reset

Reset all yearly counters:

```bash
php artisan running-number:reset invoice --force
php artisan running-number:reset order --scope=retail --force
php artisan running-number:reset order --scope=wholesale --force
```

### 3. Monitoring

Check current numbers regularly:

```bash
# Daily check
php artisan running-number:list

# Check specific type
php artisan running-number:list --type=INVOICE
```

### 4. Testing

Create test running numbers:

```bash
php artisan running-number:create test-invoice --start=9000
# Run tests
php artisan running-number:reset test-invoice --force
```

## Automation Scripts

### Bash Script for Daily Reset

```bash
#!/bin/bash
# reset-daily-numbers.sh

php artisan running-number:list --type=TICKET
php artisan running-number:reset ticket --force

echo "Daily numbers reset at $(date)"
```

### Cron Job for Monthly Reset

```bash
# Add to crontab
0 0 1 * * cd /path/to/project && php artisan running-number:reset invoice --force
```

## Error Handling

### Invalid Reset Period

```bash
php artisan running-number:create invoice --reset=invalid
```

Output:

```
Invalid reset period 'invalid'. Valid options: never, daily, monthly, yearly
```

### Missing Type Argument

```bash
php artisan running-number:reset
```

Output:

```
Not enough arguments (missing: "type").
```

## Best Practices

1. **Always list before reset**: Check current numbers before resetting

```bash
php artisan running-number:list --type=INVOICE
php artisan running-number:reset invoice --force
```

2. **Use scopes for multi-tenant**: Keep sequences separate per tenant

```bash
php artisan running-number:create order --scope=tenant_1
php artisan running-number:create order --scope=tenant_2
```

3. **Document custom types**: If creating types not in config, document them

```bash
# Document in your deployment notes
php artisan running-number:create custom-ref --start=1000
```

4. **Backup before reset**: Backup data before resetting important sequences

```bash
# Backup database first
php artisan db:backup
php artisan running-number:reset invoice --force
```

5. **Use force in automation**: Always use `--force` in automated scripts

```bash
# In deployment script
php artisan running-number:reset test-data --force
```

## Related Topics

- [Helper Functions](01-helper-functions.md) - Generate numbers programmatically
- [Model Integration](04-model-integration.md) - Automatic generation with Eloquent
- [Configuration](../02-configuration/01-overview.md) - Configure available types
- [Events](07-events.md) - Listen to number generation events

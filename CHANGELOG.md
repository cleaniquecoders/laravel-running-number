# Changelog

All notable changes to `laravel-running-number` will be documented in this file.

## 3.1.0 - 2026-03-31

### What's Changed

#### Added

- Laravel 13 support (illuminate constraints include `^13.0`)
- PHPUnit 12 compatibility
- Pest 4 support

#### Changed

- Updated `phpunit.xml.dist` for PHPUnit 12
- Standardized CI workflow (Laravel 12 + PHP 8.4/8.3)
- Updated dev dependencies (larastan, phpstan plugins, collision)

**Full Changelog**: https://github.com/cleaniquecoders/laravel-running-number/compare/3.0.0...3.1.0

## v3.0.0 - Major Feature Release (Planned)

Version 3.0.0 represents a major advancement in the package, implementing comprehensive feature enhancements and architectural improvements.

### 🚀 Major Features

#### Traitify Integration

- **Native PHP Enums Support** - Migrated from `spatie/laravel-enum` to native PHP 8.1+ enums with `cleaniquecoders/traitify`
- **UUID Support** - Full UUID support via `InteractsWithUuid` trait
- **Enum Methods** - Enhanced enum capabilities with `values()`, `labels()`, `options()`, `descriptions()`

#### Running Number Management

- **Reset/Restart Functionality** - Reset running numbers or restart from custom starting points
- **Date-Based Formats** - Generate numbers with configurable date components (e.g., `INV-2024-01-001`)
- **Multiple Sequences** - Maintain separate sequences for the same type (branch-specific, department-specific)
- **Custom Starting Numbers** - Configure custom starting numbers per type
- **Range Management** - Define and enforce number ranges with overflow detection

#### Generation Features

- **Preview Mode** - Preview next number without persisting to database
- **Bulk Generation** - Efficiently generate multiple numbers in a single operation
- **Threading Safety** - Improved concurrency handling for high-volume generation
- **Audit Trail** - Optional generation history tracking with user attribution

#### Developer Experience

- **Eloquent Trait** - New `HasRunningNumber` trait for seamless model integration
- **Artisan Commands** - CLI commands for list, reset, restart, preview, and statistics
- **Events System** - `RunningNumberGenerated` and `RunningNumberReset` events for extensibility
- **Service Container Integration** - Enhanced dependency injection support

### 📚 Documentation

- Complete documentation overhaul with structured guides
- 20+ dedicated documentation files organized in progressive learning path
- Comprehensive examples for common scenarios
- Advanced integration patterns guide
- Developer contribution guidelines

### ⚡ Breaking Changes

**Enum Migration**: Updated from Spatie enums to native PHP enums with Traitify
**Database Schema**: Added required `uuid` column to `running_numbers` table
**Configuration**: Extended with new options for reset periods, date formats, starting numbers, and audit trail

See the [Upgrade Guide](docs/06-upgrade-guide.md) for detailed migration instructions.

### 📦 Dependencies

- Added `cleaniquecoders/traitify` for UUID and enum support
- Minimum PHP 8.1+ required
- Laravel 9-12 supported

**Full Documentation**: See [docs/06-upgrade-guide.md](docs/06-upgrade-guide.md) for complete v3.0.0 upgrade guide


---

## Added Laravel 12 and PHP 8.4 Support - 2025-05-01

### What's Changed

* Bump aglipanci/laravel-pint-action from 2.3.1 to 2.4 by @dependabot in https://github.com/cleaniquecoders/laravel-running-number/pull/26
* Bump dependabot/fetch-metadata from 1.6.0 to 2.1.0 by @dependabot in https://github.com/cleaniquecoders/laravel-running-number/pull/27
* Bump ramsey/composer-install from 2 to 3 by @dependabot in https://github.com/cleaniquecoders/laravel-running-number/pull/24
* Bump actions/checkout from 3 to 4 by @dependabot in https://github.com/cleaniquecoders/laravel-running-number/pull/21
* Bump dependabot/fetch-metadata from 2.1.0 to 2.2.0 by @dependabot in https://github.com/cleaniquecoders/laravel-running-number/pull/28
* Bump dependabot/fetch-metadata from 2.2.0 to 2.3.0 by @dependabot in https://github.com/cleaniquecoders/laravel-running-number/pull/29
* Bump aglipanci/laravel-pint-action from 2.4 to 2.5 by @dependabot in https://github.com/cleaniquecoders/laravel-running-number/pull/30

**Full Changelog**: https://github.com/cleaniquecoders/laravel-running-number/compare/2.2.0...2.3.0

## Added Laravel 11 Support - 2024-03-21

### What's Changed

* Bump aglipanci/laravel-pint-action from 2.1.0 to 2.2.0 by @dependabot in https://github.com/cleaniquecoders/laravel-running-number/pull/16
* Bump dependabot/fetch-metadata from 1.3.6 to 1.4.0 by @dependabot in https://github.com/cleaniquecoders/laravel-running-number/pull/17
* Bump dependabot/fetch-metadata from 1.4.0 to 1.5.1 by @dependabot in https://github.com/cleaniquecoders/laravel-running-number/pull/18
* Bump dependabot/fetch-metadata from 1.5.1 to 1.6.0 by @dependabot in https://github.com/cleaniquecoders/laravel-running-number/pull/20
* Bump stefanzweifel/git-auto-commit-action from 4 to 5 by @dependabot in https://github.com/cleaniquecoders/laravel-running-number/pull/22
* Bump aglipanci/laravel-pint-action from 2.2.0 to 2.3.1 by @dependabot in https://github.com/cleaniquecoders/laravel-running-number/pull/23

**Full Changelog**: https://github.com/cleaniquecoders/laravel-running-number/compare/2.1.0...2.2.0

## Fixed migration class name - 2021-12-30

**Full Changelog**: https://github.com/cleaniquecoders/laravel-running-number/compare/1.0.3...1.0.4

## Update README - 2021-12-30

**Full Changelog**: https://github.com/cleaniquecoders/laravel-running-number/compare/1.0.2...1.0.3

## Update - 2021-12-30

**Full Changelog**: https://github.com/cleaniquecoders/laravel-running-number/compare/1.0.1...1.0.2

## 1.0.0 - 202X-XX-XX

- initial release

# Upgrade Guide

Complete guide for upgrading between versions of Laravel Running Number.

## 📚 Table of Contents

### [01. Upgrade Guide](01-upgrade-guide.md)

Comprehensive upgrade instructions for all versions.

**Covered Versions:**
- **v3.0.0** - Code quality improvements, testing enhancements, service container integration
- **v2.x** - Native PHP enums, UUID support, Traitify integration
- **v1.x** - Initial release

**What's Included:**
- Breaking changes
- New features
- Migration steps
- Code examples
- Troubleshooting tips

## 🚀 Quick Upgrade Paths

### From v2.x to v3.0.0

**Main Changes:**
- ✅ Code quality improvements (100% type coverage, PHPStan Level 5)
- ✅ Service container integration
- ✅ Enhanced testing (108 tests, 265 assertions)
- ✅ Improved exception handling
- ✅ Better documentation

**Action Required:**
- No breaking changes
- Update composer dependencies
- Review new features

```bash
composer update cleaniquecoders/laravel-running-number
```

### From v1.x to v2.x

**Main Changes:**
- ⚠️ **Breaking**: Replaced Spatie Enum with native PHP enums
- ⚠️ **Breaking**: Added UUID support to RunningNumber model
- ✅ New reset functionality (daily, monthly, yearly)
- ✅ Scope support for multiple sequences
- ✅ Date-based presenters

**Action Required:**
- Migrate enum usage
- Run new migrations for UUID and reset functionality
- Update configuration

```bash
composer update cleaniquecoders/laravel-running-number
php artisan migrate
```

## 📖 Version Highlights

### v3.0.0 (Current)
- **Code Quality**: PHPStan Level 5, 100% type coverage
- **Testing**: 108 comprehensive tests with concurrency and edge cases
- **Service Container**: Full DI integration
- **Documentation**: 30+ pages of comprehensive guides

### v2.x
- **PHP 8.1+ Enums**: Native enum support
- **UUID**: Built-in UUID generation
- **Reset Periods**: Auto-reset functionality
- **Scopes**: Multi-sequence support
- **Date Formats**: Multiple date-based presenters

### v1.x
- **Initial Release**: Basic sequential number generation
- **Database Persistence**: Eloquent model storage
- **Custom Formatting**: Presenter pattern
- **Configurable Types**: Type-based sequences

## 🔗 Related Documentation

- [CHANGELOG](../../CHANGELOG.md) - Detailed version history
- [Getting Started](../01-getting-started/) - New installation guide
- [Configuration](../02-configuration/) - Configuration options

---

[← Back to Main Documentation](../README.md)

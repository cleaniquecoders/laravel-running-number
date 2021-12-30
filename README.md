# Generate running number when creating new records in your table.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/laravel-running-number.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-running-number)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/cleaniquecoders/laravel-running-number/run-tests?label=tests)](https://github.com/cleaniquecoders/laravel-running-number/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/cleaniquecoders/laravel-running-number/Check%20&%20fix%20styling?label=code%20style)](https://github.com/cleaniquecoders/laravel-running-number/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/laravel-running-number.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-running-number)

Generate running number when creating new records in your table.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-running-number.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-running-number)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require cleaniquecoders/laravel-running-number
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-running-number-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-running-number-config"
```

Please make sure to configure the `config/running-number` types, in order to support your requirement.

## Usage

```php
running_number()->type($type)->generate();

// OR

use CleaniqueCoders\RunningNumber\Generator as RunningNumberGenerator;

RunningNumberGenerator::make()->type($type)->generate();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

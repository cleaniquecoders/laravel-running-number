<?php

namespace CleaniqueCoders\RunningNumber;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RunningNumberServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('running-number')
            ->hasConfigFile('running-number')
            ->hasMigration('create_running_number_table');
    }
}

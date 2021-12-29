<?php

namespace CleaniqueCoders\RunningNumber;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use CleaniqueCoders\RunningNumber\Commands\RunningNumberCommand;

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
            ->name('laravel-running-number')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-running-number_table')
            ->hasCommand(RunningNumberCommand::class);
    }
}

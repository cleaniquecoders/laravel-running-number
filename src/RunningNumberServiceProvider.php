<?php

namespace CleaniqueCoders\RunningNumber;

use CleaniqueCoders\RunningNumber\Commands\RunningNumberCommand;
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
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_running_number_table')
            ->hasCommand(RunningNumberCommand::class);
    }
}

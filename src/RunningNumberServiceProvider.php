<?php

namespace CleaniqueCoders\RunningNumber;

use CleaniqueCoders\RunningNumber\Console\Commands\CreateRunningNumberCommand;
use CleaniqueCoders\RunningNumber\Console\Commands\ListRunningNumbersCommand;
use CleaniqueCoders\RunningNumber\Console\Commands\ResetRunningNumberCommand;
use CleaniqueCoders\RunningNumber\Contracts\Generator as GeneratorContract;
use CleaniqueCoders\RunningNumber\Contracts\Presenter as PresenterContract;
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
            ->hasMigration('create_running_number_table')
            ->hasMigration('add_uuid_to_running_numbers_table')
            ->hasMigration('add_reset_functionality_to_running_numbers_table')
            ->hasMigration('add_scope_to_running_numbers_table')
            ->hasCommands([
                ListRunningNumbersCommand::class,
                ResetRunningNumberCommand::class,
                CreateRunningNumberCommand::class,
            ]);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        // Register Generator contract binding
        $this->app->bind(GeneratorContract::class, function ($app) {
            $generatorClass = config('running-number.generator', Generator::class);

            return new $generatorClass;
        });

        // Register Presenter contract binding
        $this->app->bind(PresenterContract::class, function ($app) {
            $presenterClass = config('running-number.presenter', Presenter::class);

            return new $presenterClass;
        });

        // Register singleton for Generator (optional, for shared instance)
        $this->app->singleton('running-number', function ($app) {
            return $app->make(GeneratorContract::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();
    }
}

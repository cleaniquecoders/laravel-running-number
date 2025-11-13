<?php

namespace CleaniqueCoders\RunningNumber\Console\Commands;

use CleaniqueCoders\RunningNumber\Models\RunningNumber;
use Illuminate\Console\Command;

/**
 * Create a new running number type
 */
class CreateRunningNumberCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'running-number:create
                            {type : The running number type to create}
                            {--scope= : The scope for this running number (optional)}
                            {--start=0 : Starting number (default: 0)}
                            {--reset=never : Reset period (never, daily, monthly, yearly)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new running number type';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $scope = $this->option('scope');
        $startNumber = (int) $this->option('start');
        $resetPeriod = $this->option('reset');

        // Validate reset period
        $validPeriods = ['never', 'daily', 'monthly', 'yearly'];
        if (! in_array($resetPeriod, $validPeriods, true)) {
            $this->error('Invalid reset period. Must be one of: '.implode(', ', $validPeriods));

            return Command::FAILURE;
        }

        // Check if it already exists
        /** @var \Illuminate\Database\Eloquent\Builder<RunningNumber> $query */
        $query = RunningNumber::where('type', $type);

        if ($scope !== null) {
            $query->where('scope', $scope);
        } else {
            $query->whereNull('scope');
        }

        if ($query->exists()) {
            $scopeText = $scope ? " with scope '{$scope}'" : ' (default scope)';
            $this->error("Running number type '{$type}'{$scopeText} already exists.");
            $this->line('Use running-number:list to view existing running numbers.');

            return Command::FAILURE;
        }

        // Validate type is in config
        $allowedTypes = config('running-number.types', []);
        if (! in_array($type, $allowedTypes, true)) {
            $this->warn("Warning: Type '{$type}' is not in the configured types list.");
            if (! $this->confirm('Do you want to create it anyway?', false)) {
                $this->info('Creation cancelled.');

                return Command::SUCCESS;
            }
        }

        // Create the running number
        /** @var RunningNumber $runningNumber */
        $runningNumber = RunningNumber::create([
            'type' => $type,
            'scope' => $scope,
            'number' => $startNumber,
            'reset_period' => $resetPeriod,
        ]);

        $this->newLine();
        $this->info('Successfully created running number:');
        $this->line("  UUID: {$runningNumber->uuid}");
        $this->line("  Type: {$runningNumber->type}");
        $this->line('  Scope: '.($runningNumber->scope ?? '(default)'));
        $this->line("  Starting Number: {$runningNumber->number}");
        $resetPeriod = is_object($runningNumber->reset_period) ? $runningNumber->reset_period->value : $runningNumber->reset_period;
        $this->line("  Reset Period: {$resetPeriod}");

        $this->newLine();
        $this->comment('Use running_number()->type(\''.$type.'\')->generate() to generate numbers.');

        return Command::SUCCESS;
    }
}

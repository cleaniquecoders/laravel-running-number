<?php

namespace CleaniqueCoders\RunningNumber\Console\Commands;

use CleaniqueCoders\RunningNumber\Models\RunningNumber;
use Illuminate\Console\Command;

/**
 * Reset a specific running number type to zero
 */
class ResetRunningNumberCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'running-number:reset
                            {type : The running number type to reset}
                            {--scope= : The scope to reset (optional)}
                            {--force : Force reset without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset a specific running number type to zero';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $scope = $this->option('scope');

        // Find the running number
        /** @var \Illuminate\Database\Eloquent\Builder<RunningNumber> $query */
        $query = RunningNumber::where('type', $type);

        if ($scope !== null) {
            $query->where('scope', $scope);
        } else {
            $query->whereNull('scope');
        }

        /** @var RunningNumber|null $runningNumber */
        $runningNumber = $query->first();

        if (! $runningNumber) {
            $scopeText = $scope ? " with scope '{$scope}'" : ' (default scope)';
            $this->error("Running number type '{$type}'{$scopeText} not found.");

            return Command::FAILURE;
        }

        // Show current state
        $this->info('Found running number:');
        $this->line("  Type: {$runningNumber->type}");
        $this->line('  Scope: '.($runningNumber->scope ?? '(default)'));
        $this->line("  Current Number: {$runningNumber->number}");
        $resetPeriod = is_object($runningNumber->reset_period) ? $runningNumber->reset_period->value : $runningNumber->reset_period;
        $this->line("  Reset Period: {$resetPeriod}");

        // Confirm reset
        if (! $this->option('force')) {
            if (! $this->confirm('Are you sure you want to reset this running number to zero?', false)) {
                $this->info('Reset cancelled.');

                return Command::SUCCESS;
            }
        }

        // Perform reset
        $oldNumber = $runningNumber->number;
        $runningNumber->reset();

        $this->newLine();
        $this->info("Successfully reset running number from {$oldNumber} to {$runningNumber->number}");
        $this->line("Last reset at: {$runningNumber->last_reset_at}");

        return Command::SUCCESS;
    }
}

<?php

namespace CleaniqueCoders\RunningNumber\Console\Commands;

use CleaniqueCoders\RunningNumber\Models\RunningNumber;
use Illuminate\Console\Command;

/**
 * List all running number types and their current numbers
 */
class ListRunningNumbersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'running-number:list
                            {--type= : Filter by specific type}
                            {--scope= : Filter by specific scope}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all running number types and their current numbers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = RunningNumber::query()->orderBy('type')->orderBy('scope');

        // Apply filters
        if ($type = $this->option('type')) {
            $query->where('type', $type);
        }

        if ($scope = $this->option('scope')) {
            $query->where('scope', $scope);
        }

        $runningNumbers = $query->get();

        if ($runningNumbers->isEmpty()) {
            $this->info('No running numbers found.');

            return Command::SUCCESS;
        }

        // Prepare table data
        $headers = ['UUID', 'Type', 'Scope', 'Current Number', 'Reset Period', 'Last Reset', 'Created At'];
        $rows = [];

        foreach ($runningNumbers as $rn) {
            $rows[] = [
                substr($rn->uuid ?? '', 0, 8).'...',
                $rn->type,
                $rn->scope ?? '(default)',
                $rn->number,
                is_object($rn->reset_period) ? $rn->reset_period->value : $rn->reset_period,
                $rn->last_reset_at?->format('Y-m-d H:i') ?? 'Never',
                $rn->created_at->format('Y-m-d H:i'),
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info(sprintf('Total: %d running number(s)', $runningNumbers->count()));

        return Command::SUCCESS;
    }
}

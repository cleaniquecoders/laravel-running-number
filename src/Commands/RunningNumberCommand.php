<?php

namespace CleaniqueCoders\RunningNumber\Commands;

use Illuminate\Console\Command;

class RunningNumberCommand extends Command
{
    public $signature = 'laravel-running-number';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}

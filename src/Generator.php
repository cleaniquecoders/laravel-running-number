<?php

namespace CleaniqueCoders\RunningNumber;

use CleaniqueCoders\RunningNumber\Contracts\Generator as GeneratorContract;
use CleaniqueCoders\RunningNumber\Contracts\Presenter;
use CleaniqueCoders\RunningNumber\Exceptions\InvalidRunningNumberTypeException;
use Illuminate\Support\Facades\DB;

class Generator implements GeneratorContract
{
    protected $toUpperCase = true;

    protected $presenter;

    protected $type;

    public function __construct()
    {
        $presenter = config('running-number.presenter');
        $this->presenter = new $presenter;
    }

    public static function make(): GeneratorContract
    {
        return new self;
    }

    public function formatter(Presenter $presenter): GeneratorContract
    {
        $this->presenter = $presenter;

        return $this;
    }

    public function type($type)
    {
        $this->type = $type;

        return $this;
    }

    // Casting prefix will be good.
    public function toUpperCase($value)
    {
        $this->toUpperCase = $value;

        return $this;
    }

    public function generate(): string
    {
        if (! in_array($this->type, config('running-number.types'))) {
            throw new InvalidRunningNumberTypeException('Unsupported '.$this->type);
        }

        return DB::transaction(function () {
            $this->createRunningNumberTypeIfNotExists();

            // Use lockForUpdate() to prevent race conditions
            // This locks the row until the transaction is committed
            $running_number = config('running-number.model')::where('type', $this->getType())
                ->lockForUpdate()
                ->first();

            // Check if reset is needed based on reset period
            if ($running_number->needsReset()) {
                $running_number->reset();
            }

            // Increment and save atomically within the transaction
            $running_number->increment('number');
            $running_number->refresh();

            return $this->presenter->format($this->getType(), $running_number->number);
        });
    }

    private function getType()
    {
        return $this->toUpperCase ? strtoupper($this->type) : $this->type;
    }

    private function createRunningNumberTypeIfNotExists()
    {
        // Use firstOrCreate() which is atomic and prevents race conditions
        // Multiple concurrent requests will not create duplicate types
        $resetPeriod = $this->getResetPeriod();

        config('running-number.model')::firstOrCreate(
            ['type' => $this->getType()],
            [
                'number' => 0,
                'reset_period' => $resetPeriod,
                'last_reset_at' => now(),
            ]
        );
    }

    private function getResetPeriod(): string
    {
        // Check if there's a specific reset period for this type
        $typeResetPeriods = config('running-number.reset_period.types', []);
        $type = strtolower($this->type);

        if (isset($typeResetPeriods[$type])) {
            return $typeResetPeriods[$type];
        }

        // Fall back to default reset period
        return config('running-number.reset_period.default', 'never');
    }
}

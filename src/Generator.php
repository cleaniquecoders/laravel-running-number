<?php

namespace CleaniqueCoders\RunningNumber;

use CleaniqueCoders\RunningNumber\Contracts\Generator as GeneratorContract;
use CleaniqueCoders\RunningNumber\Contracts\Presenter;
use CleaniqueCoders\RunningNumber\Enums\ResetPeriod;
use CleaniqueCoders\RunningNumber\Exceptions\InvalidRunningNumberTypeException;
use CleaniqueCoders\RunningNumber\Exceptions\MaxNumberReachedException;
use Illuminate\Support\Facades\DB;

class Generator implements GeneratorContract
{
    protected $toUpperCase = true;

    protected $presenter;

    protected $type;

    protected $scope = null;

    protected $startingNumber = 0;

    protected $maxNumber = null;

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

    public function scope(?string $scope)
    {
        $this->scope = $scope;

        return $this;
    }

    public function startFrom(int $number)
    {
        $this->startingNumber = $number;

        return $this;
    }

    public function maxNumber(int $number)
    {
        $this->maxNumber = $number;

        return $this;
    }

    public function preview(): string
    {
        if (! in_array($this->type, config('running-number.types'))) {
            throw new InvalidRunningNumberTypeException('Unsupported '.$this->type);
        }

        $query = config('running-number.model')::where('type', $this->getType());

        if ($this->scope !== null) {
            $query->where('scope', $this->scope);
        } else {
            $query->whereNull('scope');
        }

        $running_number = $query->first();

        // If type doesn't exist yet, preview what the first number would be
        if (! $running_number) {
            return $this->presenter->format($this->getType(), $this->startingNumber + 1);
        }

        // Check if reset would happen
        $nextNumber = $running_number->number;
        if ($running_number->needsReset()) {
            $nextNumber = 0;
        }

        // Get the next number (what would be generated)
        $nextNumber = $nextNumber + 1;

        return $this->presenter->format($this->getType(), $nextNumber);
    }

    public function generateBatch(int $count): array
    {
        if (! in_array($this->type, config('running-number.types'))) {
            throw new InvalidRunningNumberTypeException('Unsupported '.$this->type);
        }

        if ($count <= 0) {
            return [];
        }

        return DB::transaction(function () use ($count) {
            $this->createRunningNumberTypeIfNotExists();

            $query = config('running-number.model')::where('type', $this->getType());

            if ($this->scope !== null) {
                $query->where('scope', $this->scope);
            } else {
                $query->whereNull('scope');
            }

            $running_number = $query->lockForUpdate()->first();

            // Check if reset is needed based on reset period
            if ($running_number->needsReset()) {
                $running_number->reset();
            }

            // Check if max number will be exceeded after batch
            if ($this->maxNumber !== null && ($running_number->number + $count) > $this->maxNumber) {
                throw new MaxNumberReachedException($this->getType(), $this->maxNumber);
            }

            $numbers = [];
            $startNumber = $running_number->number + 1;

            // Generate all numbers
            for ($i = 0; $i < $count; $i++) {
                $numbers[] = $this->presenter->format($this->getType(), $startNumber + $i);
            }

            // Update the counter once
            $running_number->number = $startNumber + $count - 1;
            $running_number->save();

            return $numbers;
        });
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
            $query = config('running-number.model')::where('type', $this->getType());

            // Add scope to query if set
            if ($this->scope !== null) {
                $query->where('scope', $this->scope);
            } else {
                $query->whereNull('scope');
            }

            $running_number = $query->lockForUpdate()->first();

            // Check if reset is needed based on reset period
            if ($running_number->needsReset()) {
                $running_number->reset();
            }

            // Check if max number will be exceeded
            if ($this->maxNumber !== null && $running_number->number >= $this->maxNumber) {
                throw new MaxNumberReachedException($this->getType(), $this->maxNumber);
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
            [
                'type' => $this->getType(),
                'scope' => $this->scope,
            ],
            [
                'number' => $this->startingNumber,
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
        return config('running-number.reset_period.default', ResetPeriod::NEVER->value);
    }
}

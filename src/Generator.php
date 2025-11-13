<?php

namespace CleaniqueCoders\RunningNumber;

use CleaniqueCoders\RunningNumber\Contracts\Generator as GeneratorContract;
use CleaniqueCoders\RunningNumber\Contracts\Presenter;
use CleaniqueCoders\RunningNumber\Enums\ResetPeriod;
use CleaniqueCoders\RunningNumber\Exceptions\ConfigurationException;
use CleaniqueCoders\RunningNumber\Exceptions\InvalidRunningNumberTypeException;
use CleaniqueCoders\RunningNumber\Exceptions\MaxNumberReachedException;
use CleaniqueCoders\RunningNumber\Exceptions\NumberGenerationException;
use Illuminate\Support\Facades\DB;

/**
 * Running number generator implementation
 *
 * This class generates sequential running numbers with support for:
 * - Multiple types and scopes
 * - Custom starting numbers and maximum limits
 * - Date-based formatting
 * - Automatic reset periods (daily, monthly, yearly)
 * - Thread-safe atomic operations
 * - Bulk generation and preview mode
 *
 * @example
 * ```php
 * // Simple usage
 * $number = running_number()->type('INVOICE')->generate();
 * // Result: INVOICE001
 *
 * // With scope and custom start
 * $number = running_number()
 *     ->type('TICKET')
 *     ->scope('vip')
 *     ->startFrom(1000)
 *     ->generate();
 * // Result: TICKET1001
 *
 * // Preview without generating
 * $preview = running_number()->type('ORDER')->preview();
 * ```
 */
class Generator implements GeneratorContract
{
    /**
     * Whether to convert type to uppercase
     */
    protected bool $toUpperCase = true;

    /**
     * The presenter instance for formatting output
     */
    protected Presenter $presenter;

    /**
     * The running number type
     */
    protected string $type;

    /**
     * The scope for the running number sequence
     */
    protected ?string $scope = null;

    /**
     * The starting number for new sequences
     */
    protected int $startingNumber = 0;

    /**
     * The maximum number limit
     */
    protected ?int $maxNumber = null;

    /**
     * Create a new Generator instance
     *
     * @throws ConfigurationException If presenter configuration is invalid
     */
    public function __construct()
    {
        $presenterClass = config('running-number.presenter');

        if (! is_string($presenterClass) || ! class_exists($presenterClass)) {
            throw new ConfigurationException(
                'Invalid presenter configuration. Expected a valid class name.'
            );
        }

        $this->presenter = new $presenterClass;
    }

    /**
     * Create a new generator instance (static factory)
     *
     * @return self
     *
     * @throws ConfigurationException
     */
    public static function make(): GeneratorContract
    {
        return new self;
    }

    /**
     * Set a custom formatter for the running number
     *
     * @param  Presenter  $presenter  The presenter to format the output
     * @return self
     */
    public function formatter(Presenter $presenter): GeneratorContract
    {
        $this->presenter = $presenter;

        return $this;
    }

    /**
     * Set the type of running number to generate
     *
     * @param  string  $type  The running number type (e.g., 'INVOICE', 'ORDER')
     * @return self
     *
     * @throws ConfigurationException If type is empty or invalid
     */
    public function type(string $type): GeneratorContract
    {
        if (trim($type) === '') {
            throw new ConfigurationException('Running number type cannot be empty');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Set the scope for the running number sequence
     *
     * Scopes allow multiple independent sequences per type.
     *
     * @param  string|null  $scope  The scope identifier (null for default scope)
     * @return self
     */
    public function scope(?string $scope): GeneratorContract
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Set the starting number for new sequences
     *
     * Only applies when creating a new type/scope combination.
     * Existing sequences will continue from their current number.
     *
     * @param  int  $number  The starting number (can be negative)
     * @return self
     */
    public function startFrom(int $number): GeneratorContract
    {
        $this->startingNumber = $number;

        return $this;
    }

    /**
     * Set the maximum number limit
     *
     * Throws MaxNumberReachedException when this limit is reached.
     *
     * @param  int  $number  The maximum number (must be positive)
     * @return self
     *
     * @throws ConfigurationException If max number is not positive
     */
    public function maxNumber(int $number): GeneratorContract
    {
        if ($number <= 0) {
            throw new ConfigurationException('Maximum number must be greater than 0');
        }

        $this->maxNumber = $number;

        return $this;
    }

    /**
     * Preview the next running number without incrementing
     *
     * This is a read-only operation that shows what the next
     * generated number would be without actually creating it.
     *
     * @return string The formatted preview
     *
     * @throws InvalidRunningNumberTypeException If the type is not configured
     */
    public function preview(): string
    {
        $this->validateType();

        $query = $this->getModelQuery();
        /** @var \CleaniqueCoders\RunningNumber\Models\RunningNumber|null $running_number */
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

    /**
     * Generate multiple running numbers at once
     *
     * This is an atomic operation - either all numbers are generated
     * or none are (transaction-based). Useful for batch operations
     * like bulk order creation.
     *
     * @param  int  $count  The number of running numbers to generate
     * @return array<int, string> Array of formatted running numbers
     *
     * @throws InvalidRunningNumberTypeException If the type is not configured
     * @throws MaxNumberReachedException If batch would exceed max number
     * @throws NumberGenerationException If generation fails
     */
    public function generateBatch(int $count): array
    {
        $this->validateType();

        if ($count <= 0) {
            return [];
        }

        return DB::transaction(function () use ($count) {
            $this->createRunningNumberTypeIfNotExists();

            $query = $this->getModelQuery();
            /** @var \CleaniqueCoders\RunningNumber\Models\RunningNumber $running_number */
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

    /**
     * Control case transformation of the type prefix
     *
     * @param  bool  $value  Whether to convert type to uppercase
     * @return self
     */
    public function toUpperCase(bool $value): GeneratorContract
    {
        $this->toUpperCase = $value;

        return $this;
    }

    /**
     * Generate the next running number
     *
     * This operation is thread-safe and atomic. Uses database
     * transactions with row-level locking to prevent race conditions.
     *
     * @return string The formatted running number
     *
     * @throws InvalidRunningNumberTypeException If the type is not configured
     * @throws MaxNumberReachedException If max number is reached
     * @throws NumberGenerationException If generation fails
     */
    public function generate(): string
    {
        $this->validateType();

        return DB::transaction(function () {
            $this->createRunningNumberTypeIfNotExists();

            // Use lockForUpdate() to prevent race conditions
            // This locks the row until the transaction is committed
            $query = $this->getModelQuery();
            /** @var \CleaniqueCoders\RunningNumber\Models\RunningNumber $running_number */
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

    /**
     * Get the formatted type (with case transformation if enabled)
     */
    private function getType(): string
    {
        return $this->toUpperCase ? strtoupper($this->type) : $this->type;
    }

    /**
     * Validate that the type is configured and not empty
     *
     * @throws InvalidRunningNumberTypeException
     */
    private function validateType(): void
    {
        if (! isset($this->type)) {
            throw new InvalidRunningNumberTypeException('Running number type must be set before generating');
        }

        $allowedTypes = config('running-number.types', []);

        if (! is_array($allowedTypes)) {
            throw new ConfigurationException('Configuration "running-number.types" must be an array');
        }

        if (! in_array($this->type, $allowedTypes, true)) {
            throw new InvalidRunningNumberTypeException(
                sprintf(
                    'Unsupported running number type "%s". Allowed types: %s',
                    $this->type,
                    implode(', ', $allowedTypes)
                )
            );
        }
    }

    /**
     * Get the base query for the running number model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getModelQuery()
    {
        $modelClass = config('running-number.model');

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            throw new ConfigurationException('Invalid model configuration');
        }

        $query = $modelClass::where('type', $this->getType());

        if ($this->scope !== null) {
            $query->where('scope', $this->scope);
        } else {
            $query->whereNull('scope');
        }

        return $query;
    }

    /**
     * Create a new running number record if it doesn't exist
     *
     * Uses firstOrCreate() which is atomic and prevents race conditions.
     */
    private function createRunningNumberTypeIfNotExists(): void
    {
        $modelClass = config('running-number.model');

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            throw new ConfigurationException('Invalid model configuration');
        }

        $resetPeriod = $this->getResetPeriod();

        $modelClass::firstOrCreate(
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

    /**
     * Get the reset period for the current type
     *
     * Checks for type-specific configuration, falls back to default.
     */
    private function getResetPeriod(): string
    {
        // Check if there's a specific reset period for this type
        $typeResetPeriods = config('running-number.reset_period.types', []);
        $type = strtolower($this->type);

        if (is_array($typeResetPeriods) && isset($typeResetPeriods[$type])) {
            return $typeResetPeriods[$type];
        }

        // Fall back to default reset period
        return config('running-number.reset_period.default', ResetPeriod::NEVER->value);
    }
}

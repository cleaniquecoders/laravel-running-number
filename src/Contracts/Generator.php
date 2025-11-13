<?php

namespace CleaniqueCoders\RunningNumber\Contracts;

/**
 * Contract for running number generators
 */
interface Generator
{
    /**
     * Create a new generator instance
     */
    public static function make(): self;

    /**
     * Set a custom formatter for the running number
     *
     * @param  Presenter  $presenter  The presenter to format the output
     */
    public function formatter(Presenter $presenter): self;

    /**
     * Set the type of running number to generate
     *
     * @param  string  $type  The running number type
     */
    public function type(string $type): self;

    /**
     * Set the scope for the running number sequence
     *
     * @param  string|null  $scope  The scope identifier
     */
    public function scope(?string $scope): self;

    /**
     * Set the starting number for new sequences
     *
     * @param  int  $number  The starting number
     */
    public function startFrom(int $number): self;

    /**
     * Set the maximum number limit
     *
     * @param  int  $number  The maximum number
     */
    public function maxNumber(int $number): self;

    /**
     * Control case transformation of the type prefix
     *
     * @param  bool  $value  Whether to convert to uppercase
     */
    public function toUpperCase(bool $value): self;

    /**
     * Generate the next running number
     *
     * @return string The formatted running number
     *
     * @throws \CleaniqueCoders\RunningNumber\Exceptions\InvalidRunningNumberTypeException
     * @throws \CleaniqueCoders\RunningNumber\Exceptions\MaxNumberReachedException
     * @throws \CleaniqueCoders\RunningNumber\Exceptions\NumberGenerationException
     */
    public function generate(): string;

    /**
     * Preview the next running number without incrementing
     *
     * @return string The formatted preview
     *
     * @throws \CleaniqueCoders\RunningNumber\Exceptions\InvalidRunningNumberTypeException
     */
    public function preview(): string;

    /**
     * Generate multiple running numbers at once
     *
     * @param  int  $count  The number of running numbers to generate
     * @return array<int, string> Array of formatted running numbers
     *
     * @throws \CleaniqueCoders\RunningNumber\Exceptions\InvalidRunningNumberTypeException
     * @throws \CleaniqueCoders\RunningNumber\Exceptions\MaxNumberReachedException
     * @throws \CleaniqueCoders\RunningNumber\Exceptions\NumberGenerationException
     */
    public function generateBatch(int $count): array;
}

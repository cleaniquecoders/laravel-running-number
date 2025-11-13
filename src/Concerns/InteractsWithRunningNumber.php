<?php

namespace CleaniqueCoders\RunningNumber\Concerns;

use CleaniqueCoders\RunningNumber\Contracts\Generator;

/**
 * Trait for auto-generating running numbers on model creation
 *
 * This trait automatically generates a running number when a model is created.
 * Configure the behavior using properties on your model:
 *
 * @example
 * ```php
 * class Invoice extends Model
 * {
 *     use InteractsWithRunningNumber;
 *
 *     protected string $runningNumberField = 'invoice_number';
 *     protected string $runningNumberType = 'invoice';
 *     protected ?string $runningNumberScope = null;
 *     protected ?int $runningNumberStart = null;
 *     protected ?int $runningNumberMax = null;
 * }
 * ```
 */
trait InteractsWithRunningNumber
{
    /**
     * Boot the trait and register model events
     */
    protected static function bootInteractsWithRunningNumber(): void
    {
        static::creating(function ($model) {
            $model->generateRunningNumber();
        });
    }

    /**
     * Generate and assign the running number
     */
    public function generateRunningNumber(): void
    {
        $field = $this->getRunningNumberField();

        // Skip if field already has a value
        if (! empty($this->{$field})) {
            return;
        }

        $generator = $this->getRunningNumberGenerator();

        // Configure the generator
        $generator->type($this->getRunningNumberType());

        if ($scope = $this->getRunningNumberScope()) {
            $generator->scope($scope);
        }

        if ($start = $this->getRunningNumberStart()) {
            $generator->startFrom($start);
        }

        if ($max = $this->getRunningNumberMax()) {
            $generator->maxNumber($max);
        }

        if ($presenter = $this->getRunningNumberPresenter()) {
            $generator->formatter($presenter);
        }

        // Generate and assign the number
        $this->{$field} = $generator->generate();
    }

    /**
     * Get the generator instance
     */
    protected function getRunningNumberGenerator(): Generator
    {
        return app(Generator::class);
    }

    /**
     * Get the field name for the running number
     */
    protected function getRunningNumberField(): string
    {
        return property_exists($this, 'runningNumberField')
            ? $this->runningNumberField
            : 'number';
    }

    /**
     * Get the type for the running number
     */
    protected function getRunningNumberType(): string
    {
        if (property_exists($this, 'runningNumberType')) {
            return $this->runningNumberType;
        }

        // Default: use table name
        return $this->getTable();
    }

    /**
     * Get the scope for the running number
     */
    protected function getRunningNumberScope(): ?string
    {
        if (property_exists($this, 'runningNumberScope')) {
            // Allow dynamic scope based on model attribute
            $scope = $this->runningNumberScope;

            // If it's a callable, call it
            if (is_callable($scope)) {
                return $scope($this);
            }

            // If it's a string starting with $, treat it as an attribute name
            if (is_string($scope) && str_starts_with($scope, '$')) {
                $attribute = substr($scope, 1);

                return $this->{$attribute} ?? null;
            }

            return $scope;
        }

        return null;
    }

    /**
     * Get the starting number for the running number
     */
    protected function getRunningNumberStart(): ?int
    {
        return property_exists($this, 'runningNumberStart')
            ? $this->runningNumberStart
            : null;
    }

    /**
     * Get the maximum number for the running number
     */
    protected function getRunningNumberMax(): ?int
    {
        return property_exists($this, 'runningNumberMax')
            ? $this->runningNumberMax
            : null;
    }

    /**
     * Get the presenter for the running number
     */
    protected function getRunningNumberPresenter(): ?object
    {
        if (property_exists($this, 'runningNumberPresenter')) {
            $presenter = $this->runningNumberPresenter;

            // If it's a class name string, instantiate it
            if (is_string($presenter) && class_exists($presenter)) {
                return new $presenter;
            }

            // If it's already an instance, return it
            if (is_object($presenter)) {
                return $presenter;
            }
        }

        return null;
    }

    /**
     * Preview the next running number without generating
     */
    public function previewRunningNumber(): string
    {
        $generator = $this->getRunningNumberGenerator();

        $generator->type($this->getRunningNumberType());

        if ($scope = $this->getRunningNumberScope()) {
            $generator->scope($scope);
        }

        if ($presenter = $this->getRunningNumberPresenter()) {
            $generator->formatter($presenter);
        }

        return $generator->preview();
    }
}

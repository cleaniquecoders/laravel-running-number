<?php

namespace CleaniqueCoders\RunningNumber\Events;

use CleaniqueCoders\RunningNumber\Models\RunningNumber;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a running number is generated
 *
 * This event is fired after a running number has been successfully generated
 * and persisted to the database. Listeners can use this event for:
 * - Logging number generation
 * - Triggering notifications
 * - Syncing with external systems
 * - Audit trails
 *
 * @example
 * ```php
 * Event::listen(RunningNumberGenerated::class, function ($event) {
 *     Log::info('Generated number', [
 *         'type' => $event->type,
 *         'number' => $event->formattedNumber,
 *         'scope' => $event->scope,
 *     ]);
 * });
 * ```
 */
class RunningNumberGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The running number type
     */
    public string $type;

    /**
     * The scope (if any)
     */
    public ?string $scope;

    /**
     * The raw number value
     */
    public int $number;

    /**
     * The formatted running number string
     */
    public string $formattedNumber;

    /**
     * The RunningNumber model instance
     */
    public RunningNumber $model;

    /**
     * Create a new event instance
     *
     * @param  string  $type  The running number type
     * @param  string  $formattedNumber  The formatted number string
     * @param  RunningNumber  $model  The model instance
     * @param  string|null  $scope  The scope (if any)
     */
    public function __construct(
        string $type,
        string $formattedNumber,
        RunningNumber $model,
        ?string $scope = null
    ) {
        $this->type = $type;
        $this->formattedNumber = $formattedNumber;
        $this->model = $model;
        $this->scope = $scope;
        $this->number = $model->number;
    }

    /**
     * Get the event data as an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'scope' => $this->scope,
            'number' => $this->number,
            'formatted_number' => $this->formattedNumber,
            'uuid' => $this->model->uuid,
            'reset_period' => $this->model->reset_period,
            'last_reset_at' => $this->model->last_reset_at?->toIso8601String(),
            'created_at' => $this->model->created_at->toIso8601String(),
        ];
    }
}

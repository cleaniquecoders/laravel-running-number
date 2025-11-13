<?php

namespace CleaniqueCoders\RunningNumber\Models;

use CleaniqueCoders\RunningNumber\Enums\ResetPeriod;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Running Number Model
 *
 * @property int $id
 * @property string $uuid
 * @property int $number
 * @property string $type
 * @property string|null $scope
 * @property ResetPeriod $reset_period
 * @property \Illuminate\Support\Carbon|null $last_reset_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method bool increment(string $column, float|int $amount = 1, array $extra = [])
 * @method $this refresh()
 * @method bool save(array $options = [])
 */
class RunningNumber extends Model
{
    use InteractsWithUuid;

    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'reset_period' => ResetPeriod::class,
        'last_reset_at' => 'datetime',
    ];

    /**
     * Check if this running number needs to be reset
     */
    public function needsReset(): bool
    {
        return $this->reset_period->needsReset($this->last_reset_at);
    }

    /**
     * Reset the running number to zero
     */
    public function reset(): void
    {
        $this->number = 0;
        $this->last_reset_at = now();
        $this->save();
    }
}

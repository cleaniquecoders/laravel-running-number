<?php

namespace CleaniqueCoders\RunningNumber\Enums;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;

enum ResetPeriod: string
{
    use InteractsWithEnum;

    case NEVER = 'never';
    case DAILY = 'daily';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::NEVER => 'Never Reset',
            self::DAILY => 'Daily Reset',
            self::MONTHLY => 'Monthly Reset',
            self::YEARLY => 'Yearly Reset',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::NEVER => 'Running number never resets',
            self::DAILY => 'Running number resets every day at midnight',
            self::MONTHLY => 'Running number resets on the first day of each month',
            self::YEARLY => 'Running number resets on January 1st each year',
        };
    }

    /**
     * Check if reset is needed based on the period and last reset date
     */
    public function needsReset(?\DateTime $lastResetAt): bool
    {
        if ($lastResetAt === null) {
            return false;
        }

        $now = new \DateTime;

        return match ($this) {
            self::NEVER => false,
            self::DAILY => $lastResetAt->format('Y-m-d') !== $now->format('Y-m-d'),
            self::MONTHLY => $lastResetAt->format('Y-m') !== $now->format('Y-m'),
            self::YEARLY => $lastResetAt->format('Y') !== $now->format('Y'),
        };
    }
}

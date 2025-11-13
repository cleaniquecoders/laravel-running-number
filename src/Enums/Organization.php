<?php

namespace CleaniqueCoders\RunningNumber\Enums;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;

enum Organization: string
{
    use InteractsWithEnum;

    case ORGANIZATION = 'organization';
    case DIVISION = 'division';
    case SECTION = 'section';
    case UNIT = 'unit';
    case PROFILE = 'profile';

    public function label(): string
    {
        return match ($this) {
            self::ORGANIZATION => 'Organization',
            self::DIVISION => 'Division',
            self::SECTION => 'Section',
            self::UNIT => 'Unit',
            self::PROFILE => 'Profile',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ORGANIZATION => 'Main organization entity',
            self::DIVISION => 'Division under organization',
            self::SECTION => 'Section under division',
            self::UNIT => 'Unit under section',
            self::PROFILE => 'User profile identifier',
        };
    }
}

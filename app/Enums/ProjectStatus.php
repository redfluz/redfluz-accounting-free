<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case PLANNED = 'planned';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case ON_HOLD = 'on_hold';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Planned',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::ON_HOLD => 'On Hold',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PLANNED => 'bg-info',
            self::ACTIVE => 'bg-success',
            self::COMPLETED => 'bg-primary',
            self::ON_HOLD => 'bg-warning',
            self::CANCELLED => 'bg-secondary',
        };
    }
}

<?php

namespace App\Enums;

enum LifecycleStage: string
{
    case Plan = 'Plan';
    case Active = 'Active';
    case PhaseOut = 'Phase Out';
    case EndOfLife = 'End of Life';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function color(): string
    {
        return match ($this) {
            self::Plan => 'blue',
            self::Active => 'green',
            self::PhaseOut => 'yellow',
            self::EndOfLife => 'red',
        };
    }
}

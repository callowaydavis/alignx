<?php

namespace App\Enums;

enum FactSheetConditionOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case Contains = 'contains';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';

    public function label(): string
    {
        return match ($this) {
            self::Equals => 'equals',
            self::NotEquals => 'does not equal',
            self::Contains => 'contains',
            self::IsEmpty => 'is empty',
            self::IsNotEmpty => 'is not empty',
        };
    }

    public function requiresValue(): bool
    {
        return match ($this) {
            self::IsEmpty, self::IsNotEmpty => false,
            default => true,
        };
    }
}

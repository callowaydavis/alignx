<?php

namespace App\Enums;

enum AssigneeType: string
{
    case User = 'user';
    case Team = 'team';
    case Either = 'either';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Team => 'Team',
            self::Either => 'User or Team',
        };
    }
}

<?php

namespace App\Enums;

enum FactFieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Date = 'date';
    case Boolean = 'boolean';
    case Url = 'url';
    case Select = 'select';
    case User = 'user';
    case Group = 'group';
    case UserOrGroup = 'user_or_group';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Number => 'Number',
            self::Date => 'Date',
            self::Boolean => 'Boolean (Yes/No)',
            self::Url => 'URL',
            self::Select => 'Select (Dropdown)',
            self::User => 'User',
            self::Group => 'Group',
            self::UserOrGroup => 'User or Group',
        };
    }
}

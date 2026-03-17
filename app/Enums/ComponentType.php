<?php

namespace App\Enums;

enum ComponentType: string
{
    case Application = 'Application';
    case Interface = 'Interface';
    case DataObject = 'Data Object';
    case ItComponent = 'IT Component';
    case Provider = 'Provider';
    case Process = 'Process';
    case BusinessCapability = 'Business Capability';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function color(): string
    {
        return match ($this) {
            self::Application => 'blue',
            self::Interface => 'purple',
            self::DataObject => 'green',
            self::ItComponent => 'orange',
            self::Provider => 'teal',
            self::Process => 'yellow',
            self::BusinessCapability => 'red',
        };
    }
}

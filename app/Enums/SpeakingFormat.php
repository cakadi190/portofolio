<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;

enum SpeakingFormat: string
{
    use HasEnumOptions, HasEnumValues;

    case Online = 'online';
    case Offline = 'offline';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Offline => 'Tatap Muka',
            self::Hybrid => 'Hybrid',
        };
    }
}

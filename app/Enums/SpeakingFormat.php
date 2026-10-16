<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum SpeakingFormat: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Online = 'online';
    case Offline = 'offline';
    case Hybrid = 'hybrid';
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum ChannelPlatform: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case YouTube = 'youtube';
    case Telegram = 'telegram';
    case Instagram = 'instagram';
    case TikTok = 'tiktok';
    case Website = 'website';
}

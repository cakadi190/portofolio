<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum CommunityPlatform: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Discord = 'discord';
    case WhatsApp = 'whatsapp';
    case Telegram = 'telegram';
    case Other = 'other';
}

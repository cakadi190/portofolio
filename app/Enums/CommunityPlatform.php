<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;

enum CommunityPlatform: string
{
    use HasEnumOptions, HasEnumValues;

    case Discord = 'discord';
    case WhatsApp = 'whatsapp';
    case Telegram = 'telegram';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Discord => 'Discord',
            self::WhatsApp => 'WhatsApp',
            self::Telegram => 'Telegram',
            self::Other => 'Lainnya',
        };
    }
}

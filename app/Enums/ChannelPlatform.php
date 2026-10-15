<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;

enum ChannelPlatform: string
{
    use HasEnumOptions, HasEnumValues;

    case YouTube = 'youtube';
    case Telegram = 'telegram';
    case Instagram = 'instagram';
    case TikTok = 'tiktok';
    case Website = 'website';

    public function label(): string
    {
        return match ($this) {
            self::YouTube => 'YouTube',
            self::Telegram => 'Telegram',
            self::Instagram => 'Instagram',
            self::TikTok => 'TikTok',
            self::Website => 'Situs Web',
        };
    }
}

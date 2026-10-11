<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;

enum ContactReason: string
{
    use HasEnumOptions, HasEnumValues;

    case Connecting = 'connecting';
    case ProjectCollaboration = 'project_collaboration';
    case General = 'general';
    case Correction = 'correction';
    case Others = 'others';

    public function label(): string
    {
        return match ($this) {
            self::Connecting => 'Izin Berkoneksi',
            self::ProjectCollaboration => 'Kolaborasi Proyek',
            self::General => 'Ingin Bertanya Hal Umum',
            self::Correction => 'Koreksi / Hak Jawab',
            self::Others => 'Hal Lainnya',
        };
    }
}

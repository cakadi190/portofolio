<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;

enum SpeakingRole: string
{
    use HasEnumOptions, HasEnumValues;

    case Speaker = 'speaker';
    case Mentor = 'mentor';
    case Trainer = 'trainer';
    case Panelist = 'panelist';
    case Judge = 'judge';

    public function label(): string
    {
        return match ($this) {
            self::Speaker => 'Pembicara',
            self::Mentor => 'Mentor',
            self::Trainer => 'Pelatih',
            self::Panelist => 'Panelis',
            self::Judge => 'Juri',
        };
    }
}

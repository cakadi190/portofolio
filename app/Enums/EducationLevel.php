<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum EducationLevel: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Kindergarten = 'kg';
    case ElementarySchool = 'es';
    case JuniorHighSchool = 'jhs';
    case SeniorHighSchool = 'shs';
    case University = 'university';

    public function label(): string
    {
        return match ($this) {
            self::Kindergarten => 'TK / PAUD / TPQ',
            self::ElementarySchool => 'Sekolah Dasar (SD)',
            self::JuniorHighSchool => 'Sekolah Menengah Pertama (SMP)',
            self::SeniorHighSchool => 'Sekolah Menengah Atas (SMA/SMK)',
            self::University => 'Perguruan Tinggi',
        };
    }
}

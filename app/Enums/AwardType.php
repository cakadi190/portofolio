<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;

enum AwardType: string
{
    use HasEnumOptions, HasEnumValues;

    case Competition = 'competition';
    case Certification = 'certification';
    case Awardee = 'awardee';
    case Recognition = 'recognition';
    case Achievement = 'achievement';
    case Scholarship = 'scholarship';
    case Appointment = 'appointment';
    case Honors = 'honors';
    case Publication = 'publication';
    case Contribution = 'contribution';

    public function label(): string
    {
        return match ($this) {
            self::Competition => 'Kompetisi / Lomba',
            self::Certification => 'Sertifikasi',
            self::Awardee => 'Awardee',
            self::Recognition => 'Pengakuan',
            self::Achievement => 'Pencapaian',
            self::Scholarship => 'Beasiswa',
            self::Appointment => 'Penunjukan',
            self::Honors => 'Penghargaan Kehormatan',
            self::Publication => 'Publikasi',
            self::Contribution => 'Kontribusi',
        };
    }
}

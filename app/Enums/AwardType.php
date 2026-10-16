<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum AwardType: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

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
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum SpeakingRole: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Speaker = 'speaker';
    case Mentor = 'mentor';
    case Trainer = 'trainer';
    case Panelist = 'panelist';
    case Judge = 'judge';
}

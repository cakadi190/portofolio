<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum AcademicScoreType: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Gpa = 'gpa';
    case SchoolExam = 'school_exam';
}

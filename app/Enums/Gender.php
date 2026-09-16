<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum Gender: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Male = 'male';
    case Female = 'female';
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum CouponType: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Percentage = 'percentage';
    case Nominal = 'nominal';
}

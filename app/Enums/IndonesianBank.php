<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum IndonesianBank: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Bca = 'bca';
    case Bri = 'bri';
    case Bni = 'bni';
    case Mandiri = 'mandiri';
    case Cimb = 'cimb';
    case Permata = 'permata';
    case Btn = 'btn';
    case Danamon = 'danamon';
    case Other = 'other';
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum PaymentGateway: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Doku = 'doku';
    case Paynext = 'paynext';
    case Pakasir = 'pakasir';
    case Tripay = 'tripay';
    case Offline = 'offline';
}

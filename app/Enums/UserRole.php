<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum UserRole: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Admin = 'admin';
    case User = 'user';
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;

enum UserRole: string
{
    use HasEnumOptions, HasEnumValues;

    case Admin = 'admin';
    case Redaktur = 'redaktur';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Redaktur => 'Redaktur',
            self::User => 'Pengguna',
        };
    }
}

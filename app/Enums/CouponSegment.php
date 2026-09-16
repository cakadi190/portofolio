<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;
use App\Models\User;

enum CouponSegment: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case All = 'all';
    case NewUser = 'new_user';
    case Student = 'student';

    public function isEligible(User $user): bool
    {
        return match ($this) {
            self::All => true,
            self::NewUser => $user->transactions()->where('status', TransactionStatus::Success)->doesntExist(),
            self::Student => $user->is_student,
        };
    }
}

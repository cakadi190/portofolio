<?php

namespace App\Enums;

use App\Enums\Concerns\HasBadgeColor;
use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum BankAccountVerificationStatus: string
{
    use HasBadgeColor, HasEnumOptions, HasEnumValues, HasLabel;

    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    /**
     * @return array<string, string>
     */
    protected function badgeColors(): array
    {
        return [
            self::Pending->value => 'warning',
            self::Verified->value => 'success',
            self::Rejected->value => 'danger',
        ];
    }
}

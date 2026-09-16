<?php

namespace App\Enums;

use App\Enums\Concerns\HasBadgeColor;
use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum TenantInvitationStatus: string
{
    use HasBadgeColor, HasEnumOptions, HasEnumValues, HasLabel;

    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';

    /**
     * @return array<string, string>
     */
    protected function badgeColors(): array
    {
        return [
            self::Pending->value => 'warning',
            self::Accepted->value => 'success',
            self::Declined->value => 'danger',
            self::Expired->value => 'secondary',
        ];
    }
}

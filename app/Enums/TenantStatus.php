<?php

namespace App\Enums;

use App\Enums\Concerns\HasBadgeColor;
use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum TenantStatus: string
{
    use HasBadgeColor, HasEnumOptions, HasEnumValues, HasLabel;

    case Draft = 'draft';
    case Process = 'process';
    case Active = 'active';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    /**
     * @return array<string, string>
     */
    protected function badgeColors(): array
    {
        return [
            self::Draft->value => 'secondary',
            self::Process->value => 'warning',
            self::Active->value => 'success',
            self::Rejected->value => 'danger',
            self::Suspended->value => 'dark',
        ];
    }
}

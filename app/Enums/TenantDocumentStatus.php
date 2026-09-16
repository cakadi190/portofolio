<?php

namespace App\Enums;

use App\Enums\Concerns\HasBadgeColor;
use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum TenantDocumentStatus: string
{
    use HasBadgeColor, HasEnumOptions, HasEnumValues, HasLabel;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * @return array<string, string>
     */
    protected function badgeColors(): array
    {
        return [
            self::Pending->value => 'warning',
            self::Approved->value => 'success',
            self::Rejected->value => 'danger',
        ];
    }
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasBadgeColor;
use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum WithdrawalStatus: string
{
    use HasBadgeColor, HasEnumOptions, HasEnumValues, HasLabel;

    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Rejected = 'rejected';

    /**
     * @return array<string, string>
     */
    protected function badgeColors(): array
    {
        return [
            self::Pending->value => 'warning',
            self::Processing->value => 'info',
            self::Completed->value => 'success',
            self::Rejected->value => 'danger',
        ];
    }
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasBadgeColor;
use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum TicketOrderStatus: string
{
    use HasBadgeColor, HasEnumOptions, HasEnumValues, HasLabel;

    case NeedToFill = 'need_to_fill';
    case Filled = 'filled';
    case Attended = 'attended';

    /**
     * @return array<string, string>
     */
    protected function badgeColors(): array
    {
        return [
            self::NeedToFill->value => 'warning',
            self::Filled->value => 'info',
            self::Attended->value => 'success',
        ];
    }
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasBadgeColor;
use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum EventStatus: string
{
    use HasBadgeColor, HasEnumOptions, HasEnumValues, HasLabel;

    case Draft = 'draft';
    case Published = 'published';
    case Canceled = 'canceled';
    case Finished = 'finished';

    /**
     * @return array<string, string>
     */
    protected function badgeColors(): array
    {
        return [
            self::Draft->value => 'secondary',
            self::Published->value => 'success',
            self::Canceled->value => 'danger',
            self::Finished->value => 'dark',
        ];
    }
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasBadgeColor;
use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum CafePriceTier: string
{
    use HasBadgeColor, HasEnumOptions, HasEnumValues, HasLabel;

    case Cheap = 'cheap';
    case Medium = 'medium';
    case Expensive = 'expensive';

    /**
     * @return array<string, string>
     */
    protected function badgeColors(): array
    {
        return [
            self::Cheap->value => 'success',
            self::Medium->value => 'warning',
            self::Expensive->value => 'danger',
        ];
    }
}

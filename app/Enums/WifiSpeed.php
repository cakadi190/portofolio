<?php

namespace App\Enums;

use App\Enums\Concerns\HasBadgeColor;
use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum WifiSpeed: string
{
    use HasBadgeColor, HasEnumOptions, HasEnumValues, HasLabel;

    case Weak = 'weak';
    case Medium = 'medium';
    case Strong = 'strong';

    /**
     * @return array<string, string>
     */
    protected function badgeColors(): array
    {
        return [
            self::Weak->value => 'danger',
            self::Medium->value => 'warning',
            self::Strong->value => 'success',
        ];
    }
}

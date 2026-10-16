<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum ClarificationVerdict: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case False = 'false';
    case Misleading = 'misleading';
    case PartiallyTrue = 'partially_true';
    case TrueWithApology = 'true_with_apology';
    case Unverified = 'unverified';

    /**
     * Bootstrap contextual color used for the verdict badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::False => 'danger',
            self::Misleading => 'warning',
            self::PartiallyTrue => 'info',
            self::TrueWithApology => 'primary',
            self::Unverified => 'secondary',
        };
    }
}

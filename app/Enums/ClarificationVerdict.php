<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;

enum ClarificationVerdict: string
{
    use HasEnumOptions, HasEnumValues;

    case False = 'false';
    case Misleading = 'misleading';
    case PartiallyTrue = 'partially_true';
    case TrueWithApology = 'true_with_apology';
    case Unverified = 'unverified';

    public function label(): string
    {
        return match ($this) {
            self::False => 'Tidak Benar',
            self::Misleading => 'Menyesatkan',
            self::PartiallyTrue => 'Benar Sebagian',
            self::TrueWithApology => 'Benar, Saya Meminta Maaf',
            self::Unverified => 'Belum Terbukti',
        };
    }

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

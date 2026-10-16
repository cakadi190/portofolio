<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;
use App\Enums\Concerns\HasLabel;

enum ResumeProfile: string
{
    use HasEnumOptions, HasEnumValues, HasLabel;

    case Technical = 'technical';
    case Executive = 'executive';

    public static function default(): self
    {
        return self::Technical;
    }

    public function label(): string
    {
        return __('enums.resume_profile.label.'.$this->value);
    }

    public function description(): string
    {
        return __('enums.resume_profile.description.'.$this->value);
    }

    public function headline(): string
    {
        return __('enums.resume_profile.headline.'.$this->value);
    }

    /**
     * Download file name, descriptive per variant.
     */
    public function filename(): string
    {
        return match ($this) {
            self::Technical => 'CV-Amir-Zuhdi-Wibowo-Technical.pdf',
            self::Executive => 'CV-Amir-Zuhdi-Wibowo-Executive-Business.pdf',
        };
    }

    /**
     * Visible sections in display order; sections not listed are hidden for this profile.
     *
     * @return list<string>
     */
    public function sections(): array
    {
        return match ($this) {
            self::Technical => ['summary', 'skills', 'projects', 'career', 'education', 'certifications', 'awards', 'speaking', 'organizations'],
            self::Executive => ['summary', 'career', 'organizations', 'speaking', 'awards', 'projects', 'education', 'certifications'],
        };
    }

    public function projectLimit(): int
    {
        return match ($this) {
            self::Technical => 8,
            self::Executive => 3,
        };
    }

    public function showsProjectTechnologies(): bool
    {
        return $this === self::Technical;
    }
}

<?php

namespace App\Enums\Concerns;

trait HasBadgeColor
{
    /**
     * Override per-enum via a `badgeColors()` map when the default isn't enough.
     */
    public function badgeColor(): string
    {
        return match (true) {
            method_exists($this, 'badgeColors') => $this->badgeColors()[$this->value] ?? 'secondary',
            default => 'secondary',
        };
    }
}

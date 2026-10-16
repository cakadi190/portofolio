<?php

namespace App\Enums\Concerns;

trait HasLabel
{
    /**
     * Translated label from `lang/{locale}/enums.php` (`enums.award_type.competition`),
     * falling back to the headline of the case name when no translation exists.
     */
    public function label(): string
    {
        $key = 'enums.'.str(class_basename(static::class))->snake().'.'.$this->value;
        $line = __($key);

        return $line !== $key ? $line : str($this->name)->headline()->toString();
    }
}

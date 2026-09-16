<?php

namespace App\Enums\Concerns;

trait HasLabel
{
    public function label(): string
    {
        return str($this->name)->headline()->toString();
    }
}

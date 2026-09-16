<?php

namespace App\Traits\Models;

trait GeneratesDisplayCode
{
    public static function bootGeneratesDisplayCode(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->{$model->displayCodeColumn()})) {
                $model->{$model->displayCodeColumn()} = $model->generateUniqueDisplayCode();
            }
        });
    }

    protected function displayCodeColumn(): string
    {
        return property_exists($this, 'displayCodeColumn') ? $this->displayCodeColumn : 'display_code';
    }

    protected function displayCodeLength(): int
    {
        return property_exists($this, 'displayCodeLength') ? $this->displayCodeLength : 10;
    }

    protected function generateUniqueDisplayCode(): string
    {
        do {
            $code = (string) random_int(
                (int) str_pad('1', $this->displayCodeLength(), '0'),
                (int) str_pad('9', $this->displayCodeLength(), '9'),
            );
        } while (static::query()->where($this->displayCodeColumn(), $code)->exists());

        return $code;
    }
}

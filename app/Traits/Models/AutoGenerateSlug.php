<?php

namespace App\Traits\Models;

use Illuminate\Support\Str;

trait AutoGenerateSlug
{
    public static function bootAutoGenerateSlug(): void
    {
        static::creating(function (self $model): void {
            $source = $model->{$model->slugSourceColumn()};

            if (filled($source) && blank($model->{$model->slugColumn()})) {
                $model->{$model->slugColumn()} = $model->generateUniqueSlug($source);
            }
        });
    }

    protected function slugSourceColumn(): string
    {
        return property_exists($this, 'slugSource') ? $this->slugSource : 'name';
    }

    protected function slugColumn(): string
    {
        return property_exists($this, 'slugColumn') ? $this->slugColumn : 'slug';
    }

    protected function generateUniqueSlug(string $source): string
    {
        $base = Str::slug($source);
        $slug = $base;
        $suffix = 1;

        while (static::query()->where($this->slugColumn(), $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}

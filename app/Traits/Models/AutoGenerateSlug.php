<?php

namespace App\Traits\Models;

use Illuminate\Support\Str;

trait AutoGenerateSlug
{
    public static function bootAutoGenerateSlug(): void
    {
        static::creating(function (self $model): void {
            $source = $model->slugSource();

            if (filled($source) && blank($model->{$model->slugColumn()})) {
                $model->{$model->slugColumn()} = $model->generateUniqueSlug($source);
            }
        });

        static::updating(function (self $model): void {
            $column = $model->slugColumn();

            if (filled($model->{$column})) {
                return;
            }

            $model->{$column} = $model->getOriginal($column)
                ?: $model->generateUniqueSlug($model->slugSource());
        });
    }

    /**
     * The text the slug is built from; models with an English version override
     * `slugSourceValue()` so the slug always comes from the Indonesian text.
     */
    protected function slugSource(): string
    {
        $column = $this->slugSourceColumn();

        return method_exists($this, 'slugSourceValue') ? $this->slugSourceValue($column) : (string) $this->{$column};
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

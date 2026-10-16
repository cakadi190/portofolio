<?php

namespace App\Traits\Models;

use App\Support\AppLocale;
use Spatie\Translatable\HasTranslations;

/**
 * Public content that exists in every site language. Columns listed in the model's
 * `#[Translatable([...])]` attribute store a JSON map `{"id": "...", "en": "..."}`;
 * reading one returns the active language, falling back to Indonesian, then to any
 * language that has a value.
 *
 * Slugs are deliberately not translated: both languages share one URL slug.
 */
trait HasContentTranslations
{
    use HasTranslations;

    /**
     * Serialize translated attributes as the active language's text (as the page
     * would show it) instead of the whole language map; editors read
     * {@see self::translationPayload()} instead.
     *
     * @return array<string, mixed>
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        foreach ($this->getTranslatableAttributes() as $attribute) {
            if (array_key_exists($attribute, $attributes)) {
                $attributes[$attribute] = $this->getTranslation($attribute, $this->getLocale(), $this->useFallbackLocale());
            }
        }

        return $attributes;
    }

    /**
     * Every translatable attribute with a value (possibly empty) for each site language,
     * the shape the admin editors submit and read back.
     *
     * @return array<string, array<string, string>>
     */
    public function translationPayload(): array
    {
        $payload = [];

        foreach ($this->getTranslatableAttributes() as $attribute) {
            $values = $this->getTranslations($attribute);

            foreach (AppLocale::available() as $locale) {
                $payload[$attribute][$locale] = (string) ($values[$locale] ?? '');
            }
        }

        return $payload;
    }

    /**
     * Update the model where each translated attribute in `$data` replaces all of its
     * languages (a language left out is removed), instead of merging into them.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateReplacingTranslations(array $data): bool
    {
        foreach ($this->getTranslatableAttributes() as $attribute) {
            if (array_key_exists($attribute, $data) && is_array($data[$attribute])) {
                $this->replaceTranslations($attribute, $data[$attribute]);
                unset($data[$attribute]);
            }
        }

        return $this->update($data);
    }

    /**
     * Whether the main (first) translatable attribute has text in the given language.
     */
    public function isTranslatedTo(string $locale): bool
    {
        $main = $this->getTranslatableAttributes()[0] ?? null;

        return $main !== null && filled($this->getTranslation($main, $locale, false));
    }

    /**
     * Site languages this record is available in.
     *
     * @return list<string>
     */
    public function translatedLocales(): array
    {
        return array_values(array_filter(AppLocale::available(), fn (string $locale): bool => $this->isTranslatedTo($locale)));
    }

    /**
     * The value used to build the slug: the default language's, else whichever exists.
     */
    protected function slugSourceValue(string $attribute): string
    {
        if (! $this->isTranslatableAttribute($attribute)) {
            return (string) $this->getAttribute($attribute);
        }

        return (string) ($this->getTranslation($attribute, AppLocale::DEFAULT, false) ?: $this->getTranslation($attribute, AppLocale::DEFAULT));
    }
}

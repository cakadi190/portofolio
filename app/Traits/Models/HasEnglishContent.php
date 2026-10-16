<?php

namespace App\Traits\Models;

use App\Support\AppLocale;

/**
 * Content with an English version stored beside the Indonesian one: `title` and
 * `title_en`, `content` and `content_en`, and so on, for the attributes in the model's
 * `$englishAttributes`. Reading `$post->title` on an English page returns the English
 * text when it exists and the Indonesian text otherwise; writing always targets the
 * exact column, so editors and slugs keep working with the raw values.
 *
 * Slugs are shared by both languages.
 *
 * @property list<string> $englishAttributes
 */
trait HasEnglishContent
{
    /**
     * The language column suffix for the active site language, or null for Indonesian.
     */
    private function contentSuffix(): ?string
    {
        $locale = app()->getLocale();

        return $locale === AppLocale::DEFAULT ? null : $locale;
    }

    public function getAttribute($key): mixed
    {
        $suffix = $this->contentSuffix();

        if ($suffix !== null && in_array($key, $this->englishAttributes, true)) {
            $localized = parent::getAttribute("{$key}_{$suffix}");

            if (filled($localized)) {
                return $localized;
            }
        }

        return parent::getAttribute($key);
    }

    /**
     * Serialize the text of the active language, as a page would show it.
     *
     * @return array<string, mixed>
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();
        $suffix = $this->contentSuffix();

        if ($this->withTranslations ?? false) {
            $attributes['translations'] = $this->translationPayload();
        }

        if ($suffix === null) {
            return $attributes;
        }

        foreach ($this->englishAttributes as $attribute) {
            $localized = $attributes["{$attribute}_{$suffix}"] ?? null;

            if (array_key_exists($attribute, $attributes) && filled($localized)) {
                $attributes[$attribute] = $localized;
            }
        }

        return $attributes;
    }

    /**
     * Raw text of every attribute in each language, the shape the admin editors read.
     *
     * @return array<string, array<string, string>>
     */
    public function translationPayload(): array
    {
        $payload = [];

        foreach ($this->englishAttributes as $attribute) {
            $payload[$attribute] = [
                AppLocale::DEFAULT => (string) ($this->attributes[$attribute] ?? ''),
                'en' => (string) ($this->attributes["{$attribute}_en"] ?? ''),
            ];
        }

        return $payload;
    }

    /**
     * Whether the main (first) attribute has text in the given language.
     */
    public function isTranslatedTo(string $locale): bool
    {
        $main = $this->englishAttributes[0];
        $column = $locale === AppLocale::DEFAULT ? $main : "{$main}_{$locale}";

        return filled($this->attributes[$column] ?? null);
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
     * The Indonesian text a slug is built from, whatever the active language is.
     */
    protected function slugSourceValue(string $attribute): string
    {
        return (string) ($this->attributes[$attribute] ?? '');
    }
}

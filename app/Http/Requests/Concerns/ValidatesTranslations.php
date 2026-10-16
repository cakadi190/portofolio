<?php

namespace App\Http\Requests\Concerns;

use App\Support\AppLocale;

/**
 * Admin forms submit translated fields as `title[id]`, `title[en]`. The default
 * language is required (when the field is); every other language is optional and a
 * blank one simply means "not translated yet".
 */
trait ValidatesTranslations
{
    /**
     * Accept a plain string for a translated field as the default-language value, so
     * simple clients (and older payloads) keep working.
     *
     * @param  list<string>  $fields
     */
    protected function normalizeTranslations(array $fields): void
    {
        $normalized = [];

        foreach ($fields as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $normalized[$field] = [AppLocale::DEFAULT => $value];
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /**
     * Rules for `$field` and one entry per site language.
     *
     * @param  list<mixed>  $rules  rules of each language's value, e.g. `['string', 'max:255']`
     * @return array<string, list<mixed>>
     */
    protected function translatedRules(string $field, array $rules, bool $required = true): array
    {
        $all = [$field => [$required ? 'required' : 'nullable', 'array']];

        foreach (AppLocale::available() as $locale) {
            $all["{$field}.{$locale}"] = [
                $required && $locale === AppLocale::DEFAULT ? 'required' : 'nullable',
                ...$rules,
            ];
        }

        return $all;
    }

    /**
     * Validated translated values without blank non-default languages, ready to assign
     * to a model's translated attribute.
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    protected function withoutBlankTranslations(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_filter($data[$field], fn ($value): bool => filled($value));
            }
        }

        return $data;
    }
}

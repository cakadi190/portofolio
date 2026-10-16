<?php

namespace App\Http\Requests\Concerns;

use App\Services\HtmlSanitizer;

trait SanitizesRichText
{
    /**
     * Strip submitted WYSIWYG HTML down to a safe whitelist (no attributes, no scripts).
     * An input with no visible text becomes an empty string so `required` rejects it.
     */
    protected function sanitizeRichText(string $field): void
    {
        $html = HtmlSanitizer::richText((string) $this->input($field));

        $this->merge([
            $field => trim(strip_tags($html)) === '' ? '' : $html,
        ]);
    }

    /**
     * Store only editor HTML that survived the allow-list (the same filter public pages
     * apply when rendering), so a script smuggled into a request never reaches the database.
     *
     * @param  list<string>  $fields
     */
    protected function sanitizeEditorHtml(array $fields): void
    {
        foreach ($fields as $field) {
            if ($this->has($field)) {
                $this->merge([$field => HtmlSanitizer::clean((string) $this->input($field))]);
            }
        }
    }
}

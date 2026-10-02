<?php

namespace App\Http\Requests\Concerns;

trait SanitizesRichText
{
    /**
     * Strip submitted WYSIWYG HTML down to a safe whitelist (no attributes, no scripts).
     * An input with no visible text becomes an empty string so `required` rejects it.
     */
    protected function sanitizeRichText(string $field): void
    {
        $html = strip_tags((string) $this->input($field), '<p><br><strong><em><u><s><ul><ol><li><blockquote>');
        $html = preg_replace('/<([a-z0-9]+)\s[^>]*>/i', '<$1>', $html) ?? '';

        $this->merge([
            $field => trim(strip_tags($html)) === '' ? '' : $html,
        ]);
    }
}

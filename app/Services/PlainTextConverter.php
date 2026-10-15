<?php

namespace App\Services;

/**
 * Flattens editor HTML into readable plain text for the text/plain part of an email.
 */
class PlainTextConverter
{
    public static function fromHtml(?string $html): string
    {
        $text = preg_replace(['/<\s*br\s*\/?>/i', '/<\/(p|div|h[1-6]|blockquote|tr)>/i', '/<li[^>]*>/i'], ["\n", "\n\n", "\n- "], HtmlSanitizer::clean($html)) ?? '';
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace(['/[ \t]+\n/', '/\n{3,}/'], ["\n", "\n\n"], $text) ?? '';

        return trim($text);
    }
}

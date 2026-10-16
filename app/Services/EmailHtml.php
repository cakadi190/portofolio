<?php

namespace App\Services;

use DOMDocument;
use DOMElement;

/**
 * Prepares sanitized editor HTML for an email body: block elements get inline
 * spacing (email clients ignore or rewrite most stylesheet rules), with a bottom
 * margin on every block except the last one of its container, so a quote or reply
 * never ends in an empty gap.
 */
class EmailHtml
{
    /**
     * Bottom margin, in pixels, per block element.
     *
     * @var array<string, int>
     */
    private const array SPACING = [
        'p' => 16, 'ul' => 16, 'ol' => 16, 'blockquote' => 16, 'pre' => 16, 'table' => 16,
        'h1' => 12, 'h2' => 12, 'h3' => 12, 'h4' => 12, 'h5' => 12, 'h6' => 12,
    ];

    public static function format(?string $html): string
    {
        $clean = HtmlSanitizer::clean($html);

        if ($clean === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML('<?xml encoding="utf-8" ?><body>'.$clean.'</body>', LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach (array_keys(self::SPACING) as $tag) {
            foreach (iterator_to_array($document->getElementsByTagName($tag)) as $element) {
                self::space($element, $tag);
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);
        $output = '';

        foreach ($body?->childNodes ?? [] as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    private static function space(DOMElement $element, string $tag): void
    {
        $margin = self::isLastBlock($element) ? '0' : '0 0 '.self::SPACING[$tag].'px';
        $existing = trim($element->getAttribute('style'), " ;\t\n");

        $element->setAttribute('style', trim("margin:{$margin}".($existing !== '' ? "; {$existing}" : '')));
    }

    /**
     * Whether no element follows this one inside its parent (trailing whitespace text is ignored).
     */
    private static function isLastBlock(DOMElement $element): bool
    {
        for ($node = $element->nextSibling; $node !== null; $node = $node->nextSibling) {
            if ($node instanceof DOMElement || ($node->nodeType === XML_TEXT_NODE && trim($node->textContent) !== '')) {
                return false;
            }
        }

        return true;
    }
}

<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Reduces editor HTML to the markup the Tiptap editor can actually produce.
 *
 * Rendered with `{@html}` on public pages, so a lower-privileged writer (the
 * `redaktur` role) or a compromised account could otherwise store a script
 * that runs in an administrator's browser. Everything not allow-listed is
 * dropped: scripting/embedding elements together with their content, unknown
 * elements are unwrapped, and unknown attributes, event handlers and unsafe
 * URL schemes are removed.
 */
class HtmlSanitizer
{
    /**
     * Allowed elements and, per element, the attributes they may keep.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [], 'span' => [], 'div' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'del' => [], 'ins' => [],
        'mark' => [], 'sub' => [], 'sup' => [], 'code' => [], 'pre' => ['data-code-theme', 'data-code-title'],
        'blockquote' => [], 'ul' => [], 'ol' => ['start'], 'li' => [],
        'figure' => [], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
    ];

    /**
     * Attributes allowed on every element.
     *
     * @var list<string>
     */
    private const GLOBAL_ATTRIBUTES = ['class', 'style', 'data-type'];

    /**
     * Elements removed together with everything inside them.
     *
     * @var list<string>
     */
    private const DROPPED_WITH_CONTENT = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'noscript',
        'template', 'svg', 'math', 'form', 'textarea', 'select', 'button', 'audio', 'video', 'canvas',
        'link', 'meta', 'base', 'head', 'title',
    ];

    /**
     * Return a safe version of the given editor HTML.
     */
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><body>'.$html.'</body>',
            LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return '';
        }

        self::sanitizeChildren($body);

        $output = '';

        foreach ($body->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    private static function sanitizeChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                self::sanitizeElement($child);

                continue;
            }

            if ($child->nodeType !== XML_TEXT_NODE && $child->nodeType !== XML_CDATA_SECTION_NODE) {
                $parent->removeChild($child);
            }
        }
    }

    private static function sanitizeElement(DOMElement $element): void
    {
        $name = strtolower($element->nodeName);

        if (in_array($name, self::DROPPED_WITH_CONTENT, true)) {
            $element->parentNode?->removeChild($element);

            return;
        }

        self::sanitizeChildren($element);

        if (! array_key_exists($name, self::ALLOWED)) {
            self::unwrap($element);

            return;
        }

        self::sanitizeAttributes($element, $name);

        if ($name === 'img' && ! $element->hasAttribute('src')) {
            $element->parentNode?->removeChild($element);
        }
    }

    /**
     * Replace an element by its (already sanitized) children.
     */
    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private static function sanitizeAttributes(DOMElement $element, string $name): void
    {
        $allowed = [...self::GLOBAL_ATTRIBUTES, ...self::ALLOWED[$name]];

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $attributeName = strtolower($attribute->nodeName);
            $value = trim($attribute->nodeValue ?? '');

            $clean = in_array($attributeName, $allowed, true)
                ? self::cleanAttribute($name, $attributeName, $value)
                : null;

            if ($clean === null) {
                $element->removeAttribute($attribute->nodeName);
            } else {
                $element->setAttribute($attribute->nodeName, $clean);
            }
        }

        if ($name === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function cleanAttribute(string $element, string $attribute, string $value): ?string
    {
        return match ($attribute) {
            'href' => self::safeUrl($value, ['http', 'https', 'mailto', 'tel']),
            'src' => $element === 'img' ? self::safeUrl($value, ['http', 'https']) : null,
            'style' => self::cleanStyle($value),
            'class' => preg_match('/^[A-Za-z0-9_\- ]+$/', $value) === 1 ? $value : null,
            'target' => $value === '_blank' ? '_blank' : null,
            'rel' => preg_match('/^[a-z ]+$/i', $value) === 1 ? $value : null,
            'loading' => in_array($value, ['lazy', 'eager'], true) ? $value : null,
            'width', 'height', 'colspan', 'rowspan', 'start' => preg_match('/^\d{1,4}%?$/', $value) === 1 ? $value : null,
            'data-type', 'data-code-theme' => preg_match('/^[a-z0-9-]{1,40}$/i', $value) === 1 ? $value : null,
            'data-code-title' => preg_match('/^[^\x00-\x1f<>"]{1,60}$/u', $value) === 1 ? $value : null,
            'alt', 'title' => $value,
            default => null,
        };
    }

    /**
     * Allow absolute URLs with a listed scheme, site-relative paths and anchors.
     *
     * @param  list<string>  $schemes
     */
    private static function safeUrl(string $url, array $schemes): ?string
    {
        // Browsers ignore tabs, newlines and control characters inside a
        // scheme, so `java\tscript:` must not slip past the check below.
        $normalized = preg_replace('/[\x00-\x20\x7f]+/', '', $url) ?? '';

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $normalized, $match) === 1) {
            return in_array(strtolower($match[1]), $schemes, true) ? $url : null;
        }

        // Scheme-relative URLs (`//host`) are external; allow only real paths.
        if (str_starts_with($normalized, '//') || str_starts_with($normalized, '\\')) {
            return null;
        }

        return $url;
    }

    /**
     * Keep only the few presentation properties the editor emits, with values
     * that cannot carry a URL or an expression.
     */
    private static function cleanStyle(string $style): ?string
    {
        $color = '(?:#[0-9a-f]{3,8}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(?:,\s*[\d.]+\s*)?\)|[a-z]{3,20})';
        $rules = [
            'color' => $color,
            'background-color' => $color,
            'text-align' => '(?:left|right|center|justify)',
            'width' => '\d{1,3}(?:\.\d+)?%',
        ];

        $kept = [];

        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = array_pad(explode(':', $declaration, 2), 2, '');
            $property = strtolower(trim($property));
            $value = trim($value);

            if (isset($rules[$property]) && preg_match('/^'.$rules[$property].'$/i', $value) === 1) {
                $kept[] = "{$property}: {$value}";
            }
        }

        return $kept === [] ? null : implode('; ', $kept);
    }
}

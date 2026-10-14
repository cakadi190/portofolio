<?php

namespace App\Services;

use App\Models\Media;
use DOMDocument;
use DOMElement;

/**
 * Makes the images inside sanitized editor HTML cheap to render: intrinsic
 * `width`/`height` (no layout shift), native lazy loading, async decoding and a
 * 640 px `srcset` candidate when the media library holds a thumbnail.
 *
 * Runs after {@see HtmlSanitizer::clean()}, so the sanitizer's attribute
 * allow-list never has to know about the extra attributes.
 */
class ContentImageOptimizer
{
    /**
     * @return string The HTML with every stored `<img>` optimized; the input when it has no images.
     */
    public static function optimize(string $html): string
    {
        if ($html === '' || ! str_contains($html, '<img')) {
            return $html;
        }

        $previous = libxml_use_internal_errors(true);

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML('<?xml encoding="utf-8" ?><body>'.$html.'</body>', LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return $html;
        }

        $images = iterator_to_array($body->getElementsByTagName('img'));
        $dimensions = self::dimensionsFor(array_map(
            fn (DOMElement $image): ?string => self::storedPath($image->getAttribute('src')),
            $images,
        ));

        foreach ($images as $index => $image) {
            self::optimizeImage($image, $dimensions, $index);
        }

        $output = '';

        foreach ($body->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    /**
     * Attributes for a hero/cover image: the original, a 640 px candidate when it exists, and its size.
     *
     * @return array{src: string|null, srcset: string|null, width: int|null, height: int|null}
     */
    public static function cover(?string $path): array
    {
        $src = ImageService::url($path);
        $thumbnail = ImageService::thumbnailUrl($path);
        $size = $path ? (self::dimensionsFor([$path])[$path] ?? null) : null;

        return [
            'src' => $src,
            'srcset' => $src && $thumbnail && $thumbnail !== $src && $size
                ? "{$thumbnail} ".ImageService::THUMBNAIL_WIDTH."w, {$src} {$size['width']}w"
                : null,
            'width' => $size['width'] ?? null,
            'height' => $size['height'] ?? null,
        ];
    }

    /**
     * @param  array<string, array{width: int, height: int}>  $dimensions
     */
    private static function optimizeImage(DOMElement $image, array $dimensions, int $index): void
    {
        $path = self::storedPath($image->getAttribute('src'));
        $size = $path ? ($dimensions[$path] ?? null) : null;

        if ($size && ! $image->hasAttribute('width') && ! $image->hasAttribute('height')) {
            $image->setAttribute('width', (string) $size['width']);
            $image->setAttribute('height', (string) $size['height']);
        }

        if (! $image->hasAttribute('loading')) {
            $image->setAttribute('loading', 'lazy');
        }

        $image->setAttribute('decoding', 'async');

        if ($path && $size && $size['width'] > ImageService::THUMBNAIL_WIDTH && ! $image->hasAttribute('srcset')) {
            $thumbnail = ImageService::thumbnailUrl($path);
            $original = ImageService::url($path);

            if ($thumbnail !== $original) {
                $image->setAttribute('srcset', "{$thumbnail} ".ImageService::THUMBNAIL_WIDTH."w, {$original} {$size['width']}w");
                $image->setAttribute('sizes', '(min-width: 992px) 800px, 100vw');
            }
        }
    }

    /**
     * Media-library path behind a `/storage/...` URL, or null for any other source.
     */
    private static function storedPath(string $src): ?string
    {
        return str_starts_with($src, '/storage/') ? ltrim(substr($src, strlen('/storage/')), '/') : null;
    }

    /**
     * Intrinsic sizes of stored media, keyed by path (paths without a media record are omitted).
     *
     * @param  array<int|string, string|null>  $paths
     * @return array<string, array{width: int, height: int}>
     */
    public static function dimensionsFor(array $paths): array
    {
        $paths = array_values(array_unique(array_filter($paths)));

        if ($paths === []) {
            return [];
        }

        return Media::query()
            ->whereIn('path', $paths)
            ->whereNotNull('width')
            ->whereNotNull('height')
            ->get(['path', 'width', 'height'])
            ->mapWithKeys(fn (Media $media): array => [$media->path => ['width' => (int) $media->width, 'height' => (int) $media->height]])
            ->all();
    }
}

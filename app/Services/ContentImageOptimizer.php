<?php

namespace App\Services;

use App\Models\Media;
use DOMDocument;
use DOMElement;

/**
 * Makes the images inside sanitized editor HTML cheap to render: intrinsic
 * `width`/`height` (no layout shift), native lazy loading, async decoding and a
 * `srcset` built from the responsive variants the media library holds.
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
     * Attributes for a hero/cover image: the original, its responsive candidates and its size.
     *
     * @return array{src: string|null, srcset: string|null, sizes: string|null, width: int|null, height: int|null}
     */
    public static function cover(?string $path): array
    {
        $size = $path ? (self::dimensionsFor([$path])[$path] ?? null) : null;
        $srcset = $size['srcset'] ?? null;

        return [
            'src' => ImageService::url($path),
            'srcset' => $srcset,
            'sizes' => $srcset ? config('media.sizes.cover') : null,
            'width' => $size['width'] ?? null,
            'height' => $size['height'] ?? null,
        ];
    }

    /**
     * @param  array<string, array{width: int, height: int, srcset: string|null}>  $dimensions
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

        if ($size && $size['srcset'] && ! $image->hasAttribute('srcset')) {
            $image->setAttribute('srcset', $size['srcset']);
            $image->setAttribute('sizes', (string) config('media.sizes.content'));
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
     * Intrinsic sizes and `srcset` of stored media, keyed by path (paths without a media record are omitted).
     *
     * @param  array<int|string, string|null>  $paths
     * @return array<string, array{width: int, height: int, srcset: string|null}>
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
            ->get(['path', 'width', 'height', 'variants'])
            ->mapWithKeys(fn (Media $media): array => [$media->path => [
                'width' => (int) $media->width,
                'height' => (int) $media->height,
                'srcset' => self::srcsetFor($media),
            ]])
            ->all();
    }

    /**
     * Variant-based `srcset`; media from before variants existed fall back to
     * the lone 640 px thumbnail when its file is present.
     */
    private static function srcsetFor(Media $media): ?string
    {
        if ($media->variants !== null) {
            return ImageService::srcset($media->path, $media->width, $media->variants);
        }

        $thumbnail = ImageService::thumbnailUrl($media->path);
        $original = ImageService::url($media->path);

        return $media->width > ImageService::THUMBNAIL_WIDTH && $thumbnail !== $original
            ? "{$thumbnail} ".ImageService::THUMBNAIL_WIDTH."w, {$original} {$media->width}w"
            : null;
    }
}

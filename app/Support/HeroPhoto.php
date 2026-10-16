<?php

namespace App\Support;

use Illuminate\Support\Facades\Vite;

/**
 * The responsive homepage photo (the LCP element): one place that lists its
 * variants, shared by the `<link rel="preload">` in the layout and the early
 * `Link` header, which must stay in sync with `header-home.svelte`'s `srcset`.
 */
class HeroPhoto
{
    public const string SIZES = '(min-width: 768px) 40vw, 90vw';

    /**
     * Widths of the generated variants in `resources/images/hero`.
     *
     * @var list<int>
     */
    private const array WIDTHS = [480, 640, 720, 800];

    public static function fallbackUrl(): string
    {
        return Vite::asset('resources/images/hero/fotoku-640.webp');
    }

    public static function srcset(): string
    {
        return collect(self::WIDTHS)
            ->map(fn (int $width): string => Vite::asset("resources/images/hero/fotoku-{$width}.webp")." {$width}w")
            ->implode(', ');
    }

    /**
     * The same preload as the layout's `<link>`, as an HTTP `Link` header value, so the browser
     * can start fetching the photo as soon as the response headers arrive.
     */
    public static function linkHeader(): string
    {
        return sprintf(
            '<%s>; rel=preload; as=image; imagesrcset="%s"; imagesizes="%s"; fetchpriority=high',
            self::fallbackUrl(),
            self::srcset(),
            self::SIZES,
        );
    }
}

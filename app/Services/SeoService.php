<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Collects the search and social metadata of the current request and turns it
 * into the tag data rendered by the `<x-seo />` component.
 *
 * Static pages are described in config/seo.php by route name; dynamic pages
 * (posts, portfolios) call {@see self::set()} from their controller. Anything
 * that provides neither is served with `noindex`.
 *
 * @phpstan-type SeoOverrides array{
 *     title?: string,
 *     description?: string|null,
 *     image?: string|null,
 *     image_alt?: string|null,
 *     type?: string,
 *     published_time?: string|null,
 *     modified_time?: string|null,
 *     section?: string|null,
 *     tags?: list<string>,
 *     json_ld?: list<array<string, mixed>>,
 * }
 */
class SeoService
{
    /**
     * @var SeoOverrides
     */
    private array $overrides = [];

    public function __construct(private readonly Request $request) {}

    /**
     * Describe the current page. Later calls merge over earlier ones.
     *
     * @param  SeoOverrides  $attributes
     */
    public function set(array $attributes): static
    {
        $this->overrides = [...$this->overrides, ...$attributes];

        return $this;
    }

    /**
     * Resolve the final tag data for the current request.
     *
     * @return array<string, mixed>
     */
    public function tags(): array
    {
        $page = config('seo.pages.'.$this->request->route()?->getName());
        $isIndexable = $page !== null || isset($this->overrides['title']);

        $siteName = (string) config('app.name');
        $pageTitle = $this->overrides['title'] ?? $page['title'] ?? null;
        $title = $pageTitle ? "{$pageTitle} • {$siteName}" : $siteName;
        $description = $this->plainText(
            $this->overrides['description'] ?? $page['description'] ?? config('seo.description'),
        );

        $image = $this->overrides['image'] ?? null;
        $usesDefaultImage = blank($image);
        $image = url($usesDefaultImage ? config('seo.image') : $image);

        $canonical = $this->request->url();
        $type = $this->overrides['type'] ?? 'website';

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $isIndexable
                ? 'index,follow,max-image-preview:large,max-snippet:-1'
                : 'noindex,nofollow',
            'siteName' => $siteName,
            'locale' => config('seo.locale'),
            'type' => $type,
            'image' => $image,
            'imageAlt' => $this->overrides['image_alt'] ?? $pageTitle ?? config('seo.image_alt'),
            'imageSize' => $usesDefaultImage ? ['width' => 1200, 'height' => 630] : null,
            'twitter' => config('seo.twitter'),
            'author' => config('seo.author'),
            'publishedTime' => $this->overrides['published_time'] ?? null,
            'modifiedTime' => $this->overrides['modified_time'] ?? null,
            'section' => $this->overrides['section'] ?? null,
            'tags' => $this->overrides['tags'] ?? [],
            'jsonLd' => $isIndexable ? $this->jsonLd($title, $description, $canonical, $image) : [],
        ];
    }

    /**
     * Structured data: the site entity on the homepage plus anything a page supplied.
     *
     * @return list<array<string, mixed>>
     */
    private function jsonLd(string $title, string $description, string $canonical, string $image): array
    {
        $graph = [];

        if ($this->request->route()?->getName() === 'home') {
            $graph[] = [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => config('app.name'),
                'url' => url('/'),
                'description' => $description,
                'inLanguage' => 'id-ID',
            ];
            $graph[] = [
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'name' => config('seo.author'),
                'url' => url('/'),
                'image' => $image,
                'jobTitle' => 'Fullstack Web Developer',
                'sameAs' => config('seo.social_profiles'),
            ];
        }

        return [...$graph, ...($this->overrides['json_ld'] ?? [])];
    }

    /**
     * Strip markup and collapse whitespace so the text is safe for a meta attribute.
     */
    private function plainText(?string $html): string
    {
        return Str::limit(
            trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html)))),
            200,
        );
    }
}

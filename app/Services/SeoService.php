<?php

namespace App\Services;

use App\Enums\SystemSettingGroup;
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

    public function __construct(
        private readonly Request $request,
        private readonly SystemSettingService $settings,
    ) {}

    /**
     * The config/seo.php entry of the current route. Looked up by exact key
     * because route names contain dots, which config() would read as nesting.
     *
     * @return array<string, string>|null
     */
    private function page(): ?array
    {
        return config('seo.pages', [])[$this->request->route()?->getName()] ?? null;
    }

    /**
     * Author name shown in meta tags and structured data.
     */
    public function author(): string
    {
        return (string) $this->settings->get('seo_author', config('seo.author'));
    }

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
        $page = $this->page();
        $isIndexable = $page !== null || isset($this->overrides['title']);

        $siteName = (string) config('app.name');
        $pageTitle = $this->overrides['title'] ?? $page['title'] ?? null;
        $title = $pageTitle ? "{$pageTitle} • {$siteName}" : $siteName;
        $description = $this->plainText(
            $this->overrides['description'] ?? $page['description'] ?? $this->settings->get('seo_description', config('seo.description')),
        );

        $image = $this->overrides['image'] ?? null;
        $usesDefaultImage = blank($image);
        $defaultImage = ImageService::url($this->settings->get('seo_image')) ?? config('seo.image');
        $usesSiteImage = $usesDefaultImage && $defaultImage === config('seo.image');
        $image = url($usesDefaultImage ? $defaultImage : $image);

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
            'imageSize' => $usesSiteImage ? ['width' => 1200, 'height' => 630] : null,
            'twitter' => $this->settings->get('seo_twitter', config('seo.twitter')),
            'author' => $this->author(),
            'keywords' => $isIndexable ? $this->keywords() : null,
            'googleVerification' => $this->settings->get('google_site_verification'),
            'bingVerification' => $this->settings->get('bing_site_verification'),
            'yandexVerification' => $this->settings->get('yandex_site_verification'),
            'baiduVerification' => $this->settings->get('baidu_site_verification'),
            'facebookAppId' => $this->settings->get('facebook_app_id'),
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
        $routeName = $this->request->route()?->getName();
        $page = $this->page();

        if ($routeName === 'home') {
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
                'name' => $this->author(),
                'url' => url('/'),
                'image' => $image,
                'jobTitle' => 'Fullstack Web Developer',
                'sameAs' => $this->socialProfiles(),
            ];
        }

        if ($page !== null && $routeName !== 'home') {
            $graph[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $page['title'], 'item' => $canonical],
                ],
            ];
        }

        return [...$graph, ...($this->overrides['json_ld'] ?? [])];
    }

    /**
     * Comma separated keywords: page tags first, then the site-wide list.
     */
    private function keywords(): ?string
    {
        $keywords = collect($this->overrides['tags'] ?? [])
            ->merge(explode(',', (string) $this->settings->get('seo_keywords')))
            ->map(fn (string $keyword): string => trim($keyword))
            ->filter()
            ->unique()
            ->take(15);

        return $keywords->isEmpty() ? null : $keywords->implode(', ');
    }

    /**
     * Profile URLs of the person behind the site, from the social settings.
     *
     * @return list<string>
     */
    private function socialProfiles(): array
    {
        $profiles = collect(SystemSettingGroup::SocialMedia->fields())
            ->map(fn (array $field): mixed => $this->settings->get($field['key']))
            ->filter()
            ->values();

        return ($profiles->isEmpty() ? collect(config('seo.social_profiles')) : $profiles)->all();
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

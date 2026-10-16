<?php

namespace App\Services;

use App\Enums\SystemSettingGroup;
use App\Models\Technology;
use App\Support\AppLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
 *     author?: string|null,
 *     description?: string|null,
 *     image?: string|null,
 *     image_alt?: string|null,
 *     type?: string,
 *     published_time?: string|null,
 *     modified_time?: string|null,
 *     section?: string|null,
 *     robots?: string,
 *     locales?: list<string>,
 *     breadcrumbs?: list<array{name: string, url: string}>,
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
        $name = $this->request->route()?->getName();
        $page = config('seo.pages', [])[$name] ?? null;

        if ($page === null) {
            return null;
        }

        foreach (['title', 'description'] as $field) {
            $line = __("seo.pages.{$name}.{$field}");

            if ($line !== "seo.pages.{$name}.{$field}") {
                $page[$field] = $line;
            }
        }

        return $page;
    }

    /**
     * Author name shown in meta tags and structured data: the page's own
     * author (e.g. a post's writer) or else the site-wide setting.
     */
    public function author(): string
    {
        return ($this->overrides['author'] ?? null) ?: (string) $this->settings->get('seo_author', config('seo.author'));
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
        $brandFirst = (bool) ($page['brand_first'] ?? false) && ! isset($this->overrides['title']);
        $title = match (true) {
            blank($pageTitle) => $siteName,
            $brandFirst => "{$siteName} • {$pageTitle}",
            default => "{$pageTitle} • {$siteName}",
        };
        $description = $this->plainText(
            $this->overrides['description'] ?? $page['description'] ?? $this->settings->get('seo_description', __('seo.description')),
        );

        $image = $this->overrides['image'] ?? null;
        $usesDefaultImage = blank($image);
        $defaultImage = ImageService::url($this->settings->get('seo_image')) ?? config('seo.image');
        $usesSiteImage = $usesDefaultImage && $defaultImage === config('seo.image');
        $image = url($usesDefaultImage ? $defaultImage : $image);

        $locale = AppLocale::urlLocale();
        $plainUrl = $this->request->url();
        $canonical = AppLocale::localize($plainUrl, $locale);
        $available = $this->overrides['locales'] ?? null;
        $localeIndexable = in_array($locale, AppLocale::indexable(), true)
            && ($available === null || in_array($locale, $available, true));
        $type = $this->overrides['type'] ?? 'website';

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $this->overrides['robots'] ?? match (true) {
                ! $localeIndexable => 'noindex,follow',
                $isIndexable => 'index,follow,max-image-preview:large,max-snippet:-1',
                default => 'noindex,nofollow',
            },
            'hreflangs' => $isIndexable && $localeIndexable ? AppLocale::hreflangs($plainUrl, $available) : [],
            'siteName' => $siteName,
            'locale' => AppLocale::regionalCode($locale),
            'type' => $type,
            'image' => $image,
            'imageAlt' => $this->overrides['image_alt'] ?? $pageTitle ?? __('seo.image_alt'),
            'imageSize' => $usesSiteImage ? ['width' => 1200, 'height' => 630] : null,
            'twitter' => $this->settings->get('seo_twitter', config('seo.twitter')),
            'author' => $this->author(),
            'keywords' => $isIndexable ? $this->keywords() : null,
            'googleVerification' => $this->settings->get('google_site_verification'),
            'bingVerification' => $this->settings->get('bing_site_verification'),
            'yandexVerification' => $this->settings->get('yandex_site_verification'),
            'baiduVerification' => $this->settings->get('baidu_site_verification'),
            'facebookDomainVerification' => $this->settings->get('facebook_domain_verification'),
            'pinterestVerification' => $this->settings->get('pinterest_site_verification'),
            'tiktokVerification' => $this->settings->get('tiktok_site_verification'),
            'naverVerification' => $this->settings->get('naver_site_verification'),
            'facebookAppId' => $this->settings->get('facebook_app_id'),
            'publishedTime' => $this->overrides['published_time'] ?? null,
            'modifiedTime' => $this->overrides['modified_time'] ?? null,
            'section' => $this->overrides['section'] ?? null,
            'tags' => $this->overrides['tags'] ?? [],
            'jsonLd' => $isIndexable ? $this->jsonLd($title, $description, $canonical, $image) : [],
        ];
    }

    /**
     * Structured data: the site and person on the homepage, a typed WebPage and
     * breadcrumb trail on inner pages, plus anything a page supplied.
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
                '@id' => url('/').'#website',
                'name' => config('app.name'),
                'url' => url('/'),
                'description' => $description,
                'inLanguage' => AppLocale::languageTag(),
                'publisher' => ['@id' => url('/').'#person'],
            ];
            $graph[] = $this->person($image);
        }

        if ($page !== null && $routeName !== 'home') {
            $graph[] = $this->webPage($page, $canonical, $description, $image);
        }

        $crumbs = $this->breadcrumbTrail($page, $canonical);

        if ($routeName !== 'home' && count($crumbs) > 1) {
            $graph[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect($crumbs)->values()->map(fn (array $crumb, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb['name'],
                    'item' => $crumb['url'],
                ])->all(),
            ];
        }

        return [...$graph, ...($this->overrides['json_ld'] ?? [])];
    }

    /**
     * Home > (parent page) > current page, from a page's own breadcrumbs or config/seo.php.
     *
     * @param  array<string, string>|null  $page
     * @return list<array{name: string, url: string}>
     */
    private function breadcrumbTrail(?array $page, string $canonical): array
    {
        $trail = [['name' => __('seo.home'), 'url' => url('/')]];

        if (isset($this->overrides['breadcrumbs'])) {
            return [...$trail, ...$this->overrides['breadcrumbs']];
        }

        if ($page === null) {
            return $trail;
        }

        $parent = isset($page['parent']) ? config('seo.pages', [])[$page['parent']] ?? null : null;

        if ($parent !== null) {
            $trail[] = ['name' => $parent['title'], 'url' => route($page['parent'])];
        }

        $trail[] = ['name' => $page['title'], 'url' => $canonical];

        return $trail;
    }

    /**
     * The WebPage node of an inner page, typed by config (CollectionPage, ContactPage, ProfilePage…).
     *
     * @param  array<string, string>  $page
     * @return array<string, mixed>
     */
    private function webPage(array $page, string $canonical, string $description, string $image): array
    {
        $type = $page['schema'] ?? 'WebPage';

        return [
            '@context' => 'https://schema.org',
            '@type' => $type,
            '@id' => $canonical.'#webpage',
            'url' => $canonical,
            'name' => $page['title'],
            'description' => $description,
            'inLanguage' => AppLocale::languageTag(),
            'isPartOf' => ['@type' => 'WebSite', '@id' => url('/').'#website', 'name' => config('app.name'), 'url' => url('/')],
            ...($type === 'ProfilePage' ? ['mainEntity' => $this->person($image)] : []),
        ];
    }

    /**
     * The Person node: who the site is about, what they do and where they can be found.
     *
     * @return array<string, mixed>
     */
    private function person(string $image): array
    {
        $knowsAbout = Cache::remember('seo.person.knows_about', 3600, fn (): array => Technology::query()->orderBy('name')->limit(25)->pluck('name')->all());
        $locality = (string) ($this->settings->get('contact_address') ?: config('seo.locality'));

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            '@id' => url('/').'#person',
            'name' => $this->author(),
            'alternateName' => 'Cak Adi',
            'url' => url('/'),
            'image' => $image,
            'jobTitle' => 'Fullstack Web Developer',
            'description' => $this->plainText($this->settings->get('seo_description', __('seo.description'))),
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => $locality, 'addressCountry' => 'ID'],
            'knowsAbout' => $knowsAbout,
            'sameAs' => $this->socialProfiles(),
        ], fn (mixed $value): bool => $value !== [] && $value !== '');
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

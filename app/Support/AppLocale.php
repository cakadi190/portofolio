<?php

namespace App\Support;

use App\Services\Visitors\VisitorTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Single source of truth for the site's languages.
 *
 * Indonesian is the default and lives on the plain URLs (`/blog`); every other
 * language lives under its own prefix (`/en/blog`). Only public content paths are
 * localized: admin, settings and auth keep one URL and follow the account's
 * preference (see `ApplyAccountLocale`).
 */
class AppLocale
{
    public const string DEFAULT = 'id';

    public const string COOKIE = 'locale';

    /**
     * Request attribute holding the language prefix the visitor arrived on. Generated
     * links follow it (not the app locale), so jobs and tests build plain URLs.
     */
    public const string URL_LOCALE_ATTRIBUTE = 'url_locale';

    /**
     * @return list<string>
     */
    public static function available(): array
    {
        /** @var list<string> */
        return config('app.available_locales', [self::DEFAULT]);
    }

    /**
     * @return list<string>
     */
    public static function indexable(): array
    {
        /** @var list<string> */
        return config('app.indexable_locales', [self::DEFAULT]);
    }

    /**
     * Open Graph / BCP 47 style locale of a language (`id_ID`, `en_US`).
     */
    public static function regionalCode(string $locale): string
    {
        return ['id' => 'id_ID', 'en' => 'en_US'][$locale] ?? $locale;
    }

    /**
     * `hreflang` alternates (including `x-default`) for an unprefixed absolute URL; empty when
     * the page exists in one indexable language only.
     *
     * @param  list<string>|null  $locales  languages the page exists in; null means all indexable ones
     * @return array<string, string>
     */
    public static function hreflangs(string $url, ?array $locales = null): array
    {
        $locales = array_values(array_intersect(self::indexable(), $locales ?? self::indexable()));

        if (count($locales) < 2 || ! self::isLocalizable(parse_url($url, PHP_URL_PATH) ?: '/')) {
            return [];
        }

        $alternates = collect($locales)
            ->mapWithKeys(fn (string $locale): array => [$locale => self::localize($url, $locale)])
            ->all();

        return [...$alternates, 'x-default' => $url];
    }

    /**
     * BCP 47 language tag (`id-ID`) of the language the visitor is reading, for `inLanguage`.
     */
    public static function languageTag(): string
    {
        return str_replace('_', '-', self::regionalCode(self::urlLocale()));
    }

    public static function isAvailable(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::available(), true);
    }

    /**
     * Locales that are served from a URL prefix, i.e. every one but the default.
     *
     * @return list<string>
     */
    public static function prefixed(): array
    {
        return array_values(array_diff(self::available(), [self::DEFAULT]));
    }

    /**
     * Whether the (unprefixed) path is public content that exists in every language.
     */
    public static function isLocalizable(string $path): bool
    {
        $segment = explode('/', trim($path, '/'))[0];

        return $segment === '' || in_array($segment, config('app.localized_segments', []), true);
    }

    public static function urlLocale(): string
    {
        return request()->attributes->get(self::URL_LOCALE_ATTRIBUTE, self::DEFAULT);
    }

    /**
     * Prefix an unprefixed path or absolute URL (optionally with a query string) for the given locale.
     */
    public static function localize(string $uri, string $locale): string
    {
        preg_match('#^(?:[a-z][a-z0-9+.-]*://[^/?\#]+)?#i', $uri, $matches);
        $origin = $matches[0];

        [$path, $query] = array_pad(explode('?', substr($uri, strlen($origin)), 2), 2, null);
        $path = '/'.ltrim($path, '/');

        if ($locale === self::DEFAULT || ! self::isLocalizable($path)) {
            return $uri;
        }

        return $origin."/{$locale}".($path === '/' ? '' : $path).($query === null ? '' : "?{$query}");
    }

    /**
     * A language to offer a first-time visitor on a plain (Indonesian) public page, taken from
     * the browser's `Accept-Language` header; without one, a non-Indonesian Cloudflare country
     * header (`CF-IPCountry`) suggests English. Nothing is suggested to crawlers, to visitors
     * who already chose a language (cookie), or on pages that already have a prefix. The offer
     * is a banner, never a redirect, so every URL keeps serving one language to everyone.
     *
     * @return array{locale: string, url: string, messages: array{prompt: string, accept: string, dismiss: string}}|null
     */
    public static function suggestion(Request $request): ?array
    {
        if (! $request->isMethod('GET')
            || $request->cookies->has(self::COOKIE)
            || self::urlLocale() !== self::DEFAULT
            || ! self::isLocalizable($request->getPathInfo())
            || app(VisitorTracker::class)->isBot($request->userAgent())) {
            return null;
        }

        $other = self::prefixed();

        if ($other === []) {
            return null;
        }

        if (filled($request->header('Accept-Language'))) {
            $preferred = $request->getPreferredLanguage(self::available());
            $suggested = in_array($preferred, $other, true) ? $preferred : null;
        } else {
            $country = strtoupper((string) $request->header('CF-IPCountry'));
            $suggested = ! in_array($country, ['', 'ID', 'XX', 'T1'], true) ? $other[0] : null;
        }

        if ($suggested === null) {
            return null;
        }

        return [
            'locale' => $suggested,
            'url' => self::localize($request->getRequestUri(), $suggested),
            'messages' => [
                'prompt' => trans('suggestion.prompt', [], $suggested),
                'accept' => trans('suggestion.accept', [], $suggested),
                'dismiss' => trans('suggestion.dismiss', [], $suggested),
            ],
        ];
    }

    /**
     * The same page in every language, for the language switcher.
     *
     * @return array<string, string>|null null when the current page has a single URL
     */
    public static function alternates(Request $request): ?array
    {
        $uri = $request->getRequestUri();

        if (! self::isLocalizable($request->getPathInfo())) {
            return null;
        }

        return collect(self::available())
            ->mapWithKeys(fn (string $locale): array => [$locale => self::localize($uri, $locale)])
            ->all();
    }

    /**
     * Language choices for the switcher.
     *
     * @return list<array{code: string, label: string, flag: string}>
     */
    public static function options(): array
    {
        return collect(self::available())
            ->map(fn (string $code): array => [
                'code' => $code,
                'label' => __("locales.{$code}.label"),
                'flag' => __("locales.{$code}.flag"),
            ])
            ->all();
    }

    /**
     * Language groups (files in `lang/{locale}`) a route's page needs, beyond `common`.
     *
     * - `blog.show` → `blog`
     * - `admin.posts.index` → `admin` (shared admin text) and `admin-posts`
     * - the dashboard and account settings → `admin` plus their own group
     * - login, registration, password and two-factor routes → `auth`
     *
     * @return list<string>
     */
    public static function groupsFor(?string $routeName): array
    {
        if ($routeName === null || $routeName === '') {
            return [];
        }

        $segments = explode('.', $routeName);
        $first = $segments[0];

        return match (true) {
            $first === 'admin' => isset($segments[1]) ? ['admin', "admin-{$segments[1]}"] : ['admin'],
            in_array($first, ['dashboard', 'profile', 'security', 'user-password', 'settings'], true) => ['admin', in_array($first, ['profile', 'security'], true) ? $first : 'dashboard'],
            in_array($first, ['login', 'register', 'password', 'verification', 'two-factor', 'passkey'], true) => ['auth'],
            default => [$first],
        };
    }

    /**
     * UI strings shared with the frontend: `lang/{locale}/common.php` (keys as written) plus
     * the groups {@see self::groupsFor()} names for the route, each with its keys prefixed by
     * the group name, so a page only ships the text it can show.
     *
     * @return array<string, string>
     */
    public static function translations(?string $routeName = null): array
    {
        $strings = Arr::dot(self::group('common'));

        foreach (self::groupsFor($routeName) as $group) {
            if ($group === 'common') {
                continue;
            }

            foreach (Arr::dot(self::group($group)) as $key => $line) {
                $strings["{$group}.{$key}"] = $line;
            }
        }

        return $strings;
    }

    /**
     * @return array<string, mixed>
     */
    private static function group(string $name): array
    {
        $lines = trans($name);

        return is_array($lines) ? $lines : [];
    }
}

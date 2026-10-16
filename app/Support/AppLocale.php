<?php

namespace App\Support;

use Illuminate\Http\Request;

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
     * @return array<string, string>
     */
    public static function hreflangs(string $url): array
    {
        if (count(self::indexable()) < 2 || ! self::isLocalizable(parse_url($url, PHP_URL_PATH) ?: '/')) {
            return [];
        }

        $alternates = collect(self::indexable())
            ->mapWithKeys(fn (string $locale): array => [$locale => self::localize($url, $locale)])
            ->all();

        return [...$alternates, 'x-default' => $url];
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
     * UI strings shared with the frontend, from `lang/{locale}/{group}.php`.
     *
     * @return array<string, string>
     */
    public static function translations(string $group = 'common'): array
    {
        $lines = trans($group);

        return is_array($lines) ? $lines : [];
    }
}

import { page } from '@inertiajs/svelte';

export interface LocaleOption {
  code: string;
  label: string;
  flag: string;
}

interface I18nProps {
  locale?: string;
  locales?: LocaleOption[];
  localeAlternates?: Record<string, string> | null;
  translations?: Record<string, string>;
}

const DEFAULT_LOCALE = 'id';

/** Mirrors `localized_segments` in `config/app.php`; keep both lists in sync. */
const LOCALIZED_SEGMENTS = new Set([
  'about',
  'awards',
  'blog',
  'career',
  'certifications',
  'clarifications',
  'contact',
  'disclaimer',
  'education',
  'guestbook',
  'notes',
  'portfolio',
  'resources',
  'resume',
  'services',
  'speaking',
  'statistic',
]);

const props = (): I18nProps => page.props as I18nProps;

/** Active interface language (`id` is served from plain URLs, `en` from `/en/...`). */
export function currentLocale(): string {
  return props().locale ?? DEFAULT_LOCALE;
}

/** Pluralised `t()`: looks up `${key}.one` for a count of 1 and `${key}.other` otherwise. */
export function tChoice(
  key: string,
  count: number,
  replacements: Record<string, string | number> = {},
): string {
  return t(`${key}.${count === 1 ? 'one' : 'other'}`, {
    count,
    ...replacements,
  });
}

/** BCP 47 tag for `Intl` / `toLocale*String` formatting in the active language. */
export function intlLocale(): string {
  return currentLocale() === 'en' ? 'en-US' : 'id-ID';
}

export function localeOptions(): LocaleOption[] {
  return props().locales ?? [];
}

/** The current page in every language, or null for pages that have a single URL (admin, auth). */
export function localeAlternates(): Record<string, string> | null {
  return props().localeAlternates ?? null;
}

/**
 * Translate a shared UI string (`lang/{locale}/common.php`). Falls back to the key so
 * a missing translation is visible instead of blank. `:name` placeholders are replaced.
 */
export function t(
  key: string,
  replacements: Record<string, string | number> = {},
): string {
  let line = props().translations?.[key] ?? key;

  for (const [name, value] of Object.entries(replacements)) {
    line = line.replaceAll(`:${name}`, String(value));
  }

  return line;
}

/**
 * Language prefix of the page being viewed (`en` on `/en/blog`, `id` everywhere else,
 * including admin). Links follow this, not the interface language, so admin pages
 * never gain a prefix.
 */
export function urlLocale(): string {
  const segment = (page.url ?? '').split(/[/?#]/)[1] ?? '';

  return localeOptions().some(
    (option) => option.code === segment && segment !== DEFAULT_LOCALE,
  )
    ? segment
    : DEFAULT_LOCALE;
}

/**
 * Add the current language prefix to an internal public URL (`/blog` → `/en/blog`).
 * External, anchor-only, already-prefixed and non-public (admin, auth) URLs pass through.
 */
export function localizeHref(
  href: string,
  locale: string = urlLocale(),
): string {
  if (
    locale === DEFAULT_LOCALE ||
    !href.startsWith('/') ||
    href.startsWith('//')
  ) {
    return href;
  }

  const path = href.split(/[?#]/)[0];
  const segment = path.split('/')[1] ?? '';

  if (segment !== '' && !LOCALIZED_SEGMENTS.has(segment)) {
    return href;
  }

  return `/${locale}${path === '/' ? '' : path}${href.slice(path.length)}`;
}

/** Same as `localizeHref` for Wayfinder route definitions (`{ url, method }`). */
export function localizeTarget<T extends string | { url: string }>(
  target: T,
): T {
  if (typeof target === 'string') {
    return localizeHref(target) as T;
  }

  const pair = target as { url: string };

  return { ...pair, url: localizeHref(pair.url) } as T;
}

/** Same as `localizeHref` for a Wayfinder `.form()` definition (`{ action, method }`). */
export function localizeForm<T extends { action: string }>(form: T): T {
  return { ...form, action: localizeHref(form.action) };
}

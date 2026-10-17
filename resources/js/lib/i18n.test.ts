import { describe, expect, it, vi } from 'vitest';
import {
  currentLocale,
  localeAlternates,
  localizeForm,
  localizeHref,
  localizeTarget,
  t,
  urlLocale,
} from '@/lib/i18n';

const page = vi.hoisted(() => ({
  url: '/',
  props: {} as Record<string, unknown>,
}));

vi.mock('@inertiajs/svelte', () => ({ page }));

describe('i18n', () => {
  it('defaults to Indonesian without shared props', () => {
    page.props = {};

    expect(currentLocale()).toBe('id');
    expect(localeAlternates()).toBeNull();
  });

  it('translates shared strings and replaces placeholders', () => {
    page.props = { locale: 'en', translations: { greeting: 'Hello, :name!' } };

    expect(currentLocale()).toBe('en');
    expect(t('greeting', { name: 'Cakadi' })).toBe('Hello, Cakadi!');
  });

  it('falls back to the key when a translation is missing', () => {
    page.props = { translations: {} };

    expect(t('missing.key')).toBe('missing.key');
  });
});

describe('localized links', () => {
  const english = () => {
    page.url = '/en/blog?page=2';
    page.props = {
      locales: [
        { code: 'id', label: 'Indonesia', flag: 'fi-id' },
        { code: 'en', label: 'English', flag: 'fi-us' },
      ],
    };
  };

  it('prefixes public URLs on Indonesian pages and leaves admin URLs plain', () => {
    english();
    page.url = '/id/blog';

    expect(urlLocale()).toBe('id');
    expect(localizeHref('/blog')).toBe('/id/blog');
    expect(localizeHref('/')).toBe('/id');

    page.url = '/admin/posts';

    expect(urlLocale()).toBe('id');
    expect(localizeHref('/admin/posts')).toBe('/admin/posts');
    expect(localizeHref('/login')).toBe('/login');
  });

  it('prefixes public URLs on English pages', () => {
    english();

    expect(urlLocale()).toBe('en');
    expect(localizeHref('/blog')).toBe('/en/blog');
    expect(localizeHref('/')).toBe('/en');
    expect(localizeHref('/about#certification')).toBe(
      '/en/about#certification',
    );
    expect(localizeHref('/resources/coffee-shops?region=Ngawi')).toBe(
      '/en/resources/coffee-shops?region=Ngawi',
    );
  });

  it('leaves external, prefixed and non-public URLs alone', () => {
    english();

    expect(localizeHref('https://example.com/blog')).toBe(
      'https://example.com/blog',
    );
    expect(localizeHref('//cdn.example.com/a.js')).toBe(
      '//cdn.example.com/a.js',
    );
    expect(localizeHref('/en/blog')).toBe('/en/blog');
    expect(localizeHref('/admin')).toBe('/admin');
    expect(localizeHref('/login')).toBe('/login');
    expect(localizeHref('#top')).toBe('#top');
  });

  it('localizes Wayfinder route definitions and forms', () => {
    english();

    expect(localizeTarget({ url: '/contact', method: 'get' })).toEqual({
      url: '/en/contact',
      method: 'get',
    });
    expect(localizeTarget('/contact')).toBe('/en/contact');
    expect(localizeForm({ action: '/contact', method: 'post' })).toEqual({
      action: '/en/contact',
      method: 'post',
    });
  });
});

describe('t() placeholders', () => {
  it('does not let a short placeholder swallow a longer one', () => {
    page.props = { translations: { range: ':from to :to of :total' } };

    expect(t('range', { from: 1, to: 2, total: 3 })).toBe('1 to 2 of 3');
  });

  it('leaves unknown placeholders alone', () => {
    page.props = { translations: { line: 'Hello :name :other' } };

    expect(t('line', { name: 'Cak' })).toBe('Hello Cak :other');
  });
});

import { describe, expect, it } from 'vitest';
import { cn, formatDate, storageUrl, toUrl } from '@/lib/utils';

describe('cn', () => {
  it('merges conflicting tailwind classes, keeping the last', () => {
    expect(cn('p-2', 'p-4')).toBe('p-4');
  });

  it('drops falsy values', () => {
    expect(cn('a', false, null, undefined, 'b')).toBe('a b');
  });
});

describe('toUrl', () => {
  it('returns string hrefs unchanged', () => {
    expect(toUrl('/blog')).toBe('/blog');
  });

  it('reads the url of route objects', () => {
    expect(toUrl({ url: '/blog', method: 'get' })).toBe('/blog');
  });
});

describe('storageUrl', () => {
  it('returns null for empty values', () => {
    expect(storageUrl(null)).toBeNull();
    expect(storageUrl('')).toBeNull();
  });

  it('passes absolute and web-root values through', () => {
    expect(storageUrl('https://cdn.test/a.webp')).toBe(
      'https://cdn.test/a.webp',
    );
    expect(storageUrl('/img/a.webp')).toBe('/img/a.webp');
  });

  it('prefixes relative paths with /storage', () => {
    expect(storageUrl('media/a.webp')).toBe('/storage/media/a.webp');
  });
});

describe('formatDate', () => {
  it('formats dates in Indonesian', () => {
    expect(formatDate('2022-07-12')).toBe('12 Juli 2022');
  });

  it('returns an empty string for empty values', () => {
    expect(formatDate(null)).toBe('');
    expect(formatDate(undefined)).toBe('');
    expect(formatDate('')).toBe('');
  });

  it('returns the raw value when unparseable', () => {
    expect(formatDate('bukan tanggal')).toBe('bukan tanggal');
  });
});

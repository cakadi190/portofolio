import { describe, expect, it } from 'vitest';
import { isPreviewable, linkDisplay, normalizeLinkUrl } from '@/lib/link-url';

describe('normalizeLinkUrl', () => {
  it.each([
    ['https://a.test/x?y=1', 'https://a.test/x?y=1'],
    ['  http://a.test ', 'http://a.test'],
    ['/blog/post', '/blog/post'],
    ['#section', '#section'],
    ['mailto:a@b.co', 'mailto:a@b.co'],
    ['tel:+62123', 'tel:+62123'],
  ])('keeps %s', (input, expected) => {
    expect(normalizeLinkUrl(input)).toBe(expected);
  });

  it('adds a scheme to bare domains and emails', () => {
    expect(normalizeLinkUrl('example.com/path')).toBe(
      'https://example.com/path',
    );
    expect(normalizeLinkUrl('wp.kodinus.id')).toBe('https://wp.kodinus.id');
    expect(normalizeLinkUrl('nama@email.com')).toBe('mailto:nama@email.com');
  });

  it.each([
    '',
    '   ',
    'javascript:alert(1)',
    'just words',
    'two words.com',
    'data:text/html,x',
  ])('rejects %j', (input) => {
    expect(normalizeLinkUrl(input)).toBeNull();
  });
});

describe('link display helpers', () => {
  it('shows host and path of absolute links', () => {
    expect(linkDisplay('https://wp.kodinus.id/?page_id=2')).toBe(
      'wp.kodinus.id/?page_id=2',
    );
    expect(linkDisplay('https://example.com/')).toBe('example.com');
    expect(linkDisplay('/blog')).toBe('/blog');
  });

  it('only previews absolute http(s) links', () => {
    expect(isPreviewable('https://a.test')).toBe(true);
    expect(isPreviewable('/blog')).toBe(false);
    expect(isPreviewable('mailto:a@b.co')).toBe(false);
  });
});

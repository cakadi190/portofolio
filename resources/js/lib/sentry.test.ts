import { describe, expect, it } from 'vitest';
import { scrubEvent, scrubText } from '@/lib/sentry';

describe('scrubText', () => {
  it('masks emails in free text', () => {
    expect(scrubText('gagal kirim ke a@b.com')).toBe('gagal kirim ke [email]');
  });

  it('filters sensitive query values and masks emails in the rest', () => {
    const result = scrubText('/login?token=abc&ref=a@b.com&page=2');

    expect(result).toContain('token=%5BFiltered%5D');
    expect(result).toContain('ref=%5Bemail%5D');
    expect(result).toContain('page=2');
    expect(result).not.toContain('abc');
  });
});

describe('scrubEvent', () => {
  it('removes cookies, bodies, personal user fields and sensitive extras', () => {
    const event = scrubEvent({
      type: undefined,
      request: {
        url: '/x?email=a@b.com',
        cookies: { s: '1' },
        data: { a: 1 },
        headers: { Cookie: 'x', 'User-Agent': 'ua' },
      },
      user: {
        id: 'ref',
        email: 'a@b.com',
        ip_address: '1.1.1.1',
        segment: 'admin',
      },
      extra: { password: 'p', note: 'hubungi a@b.com' },
    } as never) as never as Record<string, any>;

    expect(event.request.cookies).toBeUndefined();
    expect(event.request.data).toBeUndefined();
    expect(event.request.headers).toEqual({ 'User-Agent': 'ua' });
    expect(event.request.url).not.toContain('a@b.com');
    expect(event.user).toEqual({ id: 'ref', segment: 'admin' });
    expect(event.extra).toEqual({
      password: '[Filtered]',
      note: 'hubungi [email]',
    });
  });
});

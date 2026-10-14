import { describe, expect, it } from 'vitest';
import { badgeStyle } from '@/lib/badge';

describe('badgeStyle', () => {
  it('uses dark text on light backgrounds', () => {
    expect(badgeStyle('#0ea5e9')).toBe(
      'background-color: #0ea5e9; color: #000000',
    );
  });

  it('uses white text on dark backgrounds, including 3-digit hex', () => {
    expect(badgeStyle('#1e2b41')).toContain('color: #ffffff');
    expect(badgeStyle('#000')).toContain('color: #ffffff');
  });

  it('falls back to grey for empty or unparsable colours', () => {
    expect(badgeStyle(null)).toContain('background-color: #6c757d');
    expect(badgeStyle('red')).toContain(
      'background-color: red; color: #000000',
    );
  });
});

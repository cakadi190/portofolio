import { describe, expect, it } from 'vitest';
import { THEME_STORAGE_KEY } from '@/lib/theme';

describe('theme', () => {
  it('uses the key shared with the anti-FOUC boot script', () => {
    expect(THEME_STORAGE_KEY).toBe('catatancakadi:theme');
  });
});

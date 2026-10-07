import { describe, expect, it } from 'vitest';
import { techIcon } from '@/lib/tech-icon';

describe('techIcon', () => {
  it('maps known names case-insensitively and trims whitespace', () => {
    expect(techIcon('  Laravel ')).toBe('devicon:laravel');
    expect(techIcon('Node.js')).toBe('devicon:nodejs');
  });

  it('falls back to a generic icon', () => {
    expect(techIcon('Cobol')).toBe('lucide:code');
  });
});

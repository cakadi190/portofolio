import { describe, expect, it } from 'vitest';
import { techIcon } from '@/lib/tech-icon';

describe('techIcon', () => {
  it('maps known names case-insensitively and trims whitespace', () => {
    expect(techIcon('  Laravel ')).toBe('devicon:laravel');
    expect(techIcon('Node.js')).toBe('devicon:nodejs');
  });

  it('tolerates spacing, dots and dashes in spelling variants', () => {
    expect(techIcon('Tailwind CSS')).toBe('devicon:tailwindcss');
    expect(techIcon('Vue.js')).toBe('devicon:vuejs');
    expect(techIcon('Node JS')).toBe('devicon:nodejs');
    expect(techIcon('Next-js')).toBe('devicon:nextjs');
  });

  it('maps every technology used by the seeders', () => {
    for (const name of [
      'Supabase',
      'Vercel',
      'NuxtJS',
      'VueJS',
      'HTML5',
      'CSS3',
      'jQuery',
    ]) {
      expect(techIcon(name)).not.toBe('lucide:code');
    }
  });

  it('falls back to a generic icon', () => {
    expect(techIcon('Cobol')).toBe('lucide:code');
  });
});

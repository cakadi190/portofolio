import { describe, expect, it } from 'vitest';
import { compact, findPhraseMatches, textRunsToWords } from '@/lib/ocr/phrase';
import type { PageWord } from '@/lib/ocr/phrase';

const word = (text: string, line: number, index: number): PageWord => ({
  text,
  line,
  rect: [index * 10, line * 20, index * 10 + 9, line * 20 + 10],
});

describe('compact', () => {
  it('drops case, diacritics and punctuation', () => {
    expect(compact('Co-Founder, CÓ!')).toBe(
      'cofoundercó'.normalize('NFD').replace(/[̀-ͯ]/g, ''),
    );
  });
});

describe('textRunsToWords', () => {
  it('splits runs into words and starts a new line when the baseline moves', () => {
    const words = textRunsToWords([
      {
        str: 'halo dunia',
        transform: [1, 0, 0, 10, 0, 100],
        width: 100,
        height: 10,
      },
      {
        str: 'baris dua',
        transform: [1, 0, 0, 10, 0, 80],
        width: 90,
        height: 10,
      },
    ]);

    expect(words.map((w) => [w.text, w.line])).toEqual([
      ['halo', 0],
      ['dunia', 0],
      ['baris', 1],
      ['dua', 1],
    ]);
    expect(words[1].rect[0]).toBeCloseTo(50);
  });
});

describe('findPhraseMatches', () => {
  const words = [
    word('Software', 0, 0),
    word('Engineer', 0, 1),
    word('dan', 1, 0),
    word('Wirausahawan', 1, 1),
    word('Co-Founder', 2, 0),
  ];

  it('finds a phrase that wraps across lines and returns one rect per line', () => {
    const [match] = findPhraseMatches(words, 'engineer dan wirausa');

    expect(match.rects).toHaveLength(2);
    expect(match.snippet).toContain('Software Engineer dan');
  });

  it('ignores punctuation and case', () => {
    expect(findPhraseMatches(words, 'co founder')).toHaveLength(1);
  });

  it('finds every occurrence', () => {
    expect(
      findPhraseMatches([word('ab', 0, 0), word('ab', 0, 1)], 'ab'),
    ).toHaveLength(2);
  });

  it('only tolerates misread characters when fuzzy is enabled', () => {
    const scanned = [word('Wirausahawon', 0, 0)];

    expect(findPhraseMatches(scanned, 'Wirausahawan')).toEqual([]);
    expect(findPhraseMatches(scanned, 'Wirausahawan', true)).toHaveLength(1);
  });

  it('returns nothing for an empty term', () => {
    expect(findPhraseMatches(words, '  ')).toEqual([]);
  });
});

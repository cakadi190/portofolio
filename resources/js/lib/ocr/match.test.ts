import { describe, expect, it } from 'vitest';
import { editDistance, findOcrMatches } from '@/lib/ocr/match';
import type { OcrLine } from '@/lib/ocr/types';

const line = (...words: string[]): OcrLine =>
  words.map((text, index) => ({
    text,
    rect: [index * 10, 0, index * 10 + 9, 5],
  }));

describe('editDistance', () => {
  it('computes Levenshtein distance', () => {
    expect(editDistance('kitten', 'sitting')).toBe(3);
    expect(editDistance('', 'abc')).toBe(3);
    expect(editDistance('same', 'same')).toBe(0);
  });
});

describe('findOcrMatches', () => {
  it('returns the union rect of exactly matched words', () => {
    const matches = findOcrMatches(
      [line('hello', 'brave', 'world')],
      'brave world',
      /brave world/gi,
      false,
    );

    expect(matches).toHaveLength(1);
    expect(matches[0].rect).toEqual([10, 0, 29, 5]);
    expect(matches[0].snippet).toBe('hello brave world');
  });

  it('falls back to fuzzy matching for OCR typos', () => {
    const matches = findOcrMatches(
      [line('the', 'catalogue', 'page')],
      'catalcgue',
      /catalcgue/gi,
      false,
    );

    expect(matches).toHaveLength(1);
    expect(matches[0].rect).toEqual([10, 0, 19, 5]);
  });

  it('does not fuzzy match short terms', () => {
    expect(findOcrMatches([line('cat')], 'car', /car/gi, false)).toHaveLength(
      0,
    );
  });

  it('returns nothing when nothing matches', () => {
    expect(
      findOcrMatches([line('alpha', 'beta')], 'zzzzzzzz', /zzzzzzzz/g, false),
    ).toEqual([]);
  });
});

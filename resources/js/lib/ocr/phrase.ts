import { editDistance } from './match';
import type { Rect } from './types';

export type PageWord = { text: string; rect: Rect; line: number };
export type PhraseMatch = { rects: Rect[]; snippet: string };
export type TextRun = {
  str: string;
  transform: number[];
  width: number;
  height: number;
};

/** Lowercases and strips diacritics and punctuation so "Co-Founder", "co founder" and "CÓ FOUNDER" compare equal. */
export function compact(value: string): string {
  return value
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/[^\p{L}\p{N}]+/gu, '');
}

/** Splits PDF text runs into words with proportional boxes; a change of baseline starts a new line. */
export function textRunsToWords(runs: TextRun[]): PageWord[] {
  const words: PageWord[] = [];
  let line = 0;
  let baseline: number | null = null;

  for (const run of runs) {
    if (run.str.trim() === '') {
      continue;
    }

    const height = run.height || Math.abs(run.transform[3]);
    const y = run.transform[5];

    if (baseline !== null && Math.abs(y - baseline) > height * 0.5) {
      line++;
    }

    baseline = y;

    for (const match of run.str.matchAll(/\S+/g)) {
      const left = run.transform[4] + run.width * (match.index / run.str.length);
      const width = run.width * (match[0].length / run.str.length);

      words.push({
        text: match[0],
        rect: [left, y - height * 0.2, left + width, y + height],
        line,
      });
    }
  }

  return words;
}

function allowedErrors(length: number): number {
  return length >= 12 ? 3 : length >= 8 ? 2 : length >= 5 ? 1 : 0;
}

function build(words: PageWord[], first: number, last: number): PhraseMatch {
  const covered = words.slice(first, last + 1);
  const rects = new Map<number, Rect>();

  for (const word of covered) {
    const rect = rects.get(word.line);

    rects.set(
      word.line,
      rect
        ? [
            Math.min(rect[0], word.rect[0]),
            Math.min(rect[1], word.rect[1]),
            Math.max(rect[2], word.rect[2]),
            Math.max(rect[3], word.rect[3]),
          ]
        : [...word.rect],
    );
  }

  return {
    rects: [...rects.values()],
    snippet: words
      .slice(Math.max(0, first - 4), last + 7)
      .map((word) => word.text)
      .join(' '),
  };
}

/**
 * Finds every occurrence of a phrase across words, lines and punctuation. With `fuzzy` (OCR output)
 * it also tolerates a few misread characters when a page has no exact hit.
 */
export function findPhraseMatches(
  words: PageWord[],
  term: string,
  fuzzy = false,
): PhraseMatch[] {
  const needle = compact(term);

  if (needle === '' || words.length === 0) {
    return [];
  }

  let haystack = '';
  const owner: number[] = [];

  words.forEach((word, index) => {
    const part = compact(word.text);
    haystack += part;
    owner.push(...Array.from({ length: part.length }, () => index));
  });

  const results: PhraseMatch[] = [];
  let from = haystack.indexOf(needle);

  while (from !== -1) {
    results.push(build(words, owner[from], owner[from + needle.length - 1]));
    from = haystack.indexOf(needle, from + needle.length);
  }

  const allowed = allowedErrors(needle.length);

  if (results.length > 0 || !fuzzy || allowed === 0) {
    return results;
  }

  const size = Math.max(1, term.trim().split(/\s+/).length);
  let skipUntil = -1;

  for (let start = 0; start < words.length; start++) {
    if (start <= skipUntil) {
      continue;
    }

    for (const length of [size, size + 1, size - 1]) {
      if (length < 1 || start + length > words.length) {
        continue;
      }

      const candidate = compact(
        words
          .slice(start, start + length)
          .map((word) => word.text)
          .join(' '),
      );

      if (
        Math.abs(candidate.length - needle.length) <= allowed &&
        editDistance(candidate, needle) <= allowed
      ) {
        results.push(build(words, start, start + length - 1));
        skipUntil = start + length - 1;
        break;
      }
    }
  }

  return results;
}

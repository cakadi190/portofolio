import type { OcrLine, OcrMatch } from './types';

export function editDistance(a: string, b: string): number {
  let previous = Array.from({ length: b.length + 1 }, (_, index) => index);

  for (let i = 1; i <= a.length; i++) {
    const current = [i];

    for (let j = 1; j <= b.length; j++) {
      current[j] = Math.min(
        previous[j] + 1,
        current[j - 1] + 1,
        previous[j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1),
      );
    }

    previous = current;
  }

  return previous[b.length];
}

/** Finds exact (via pattern) and fuzzy matches of the term inside recognized lines. */
export function findOcrMatches(
  lines: OcrLine[],
  term: string,
  pattern: RegExp,
  caseSensitive: boolean,
): OcrMatch[] {
  const needle = caseSensitive ? term : term.toLowerCase();
  const allowed = needle.length >= 9 ? 2 : needle.length >= 5 ? 1 : 0;
  const size = needle.split(/\s+/).length;
  const results: OcrMatch[] = [];

  for (const line of lines) {
    let text = '';
    const spans = line.map((word) => {
      text += (text.length > 0 ? ' ' : '') + word.text;

      return {
        start: text.length - word.text.length,
        end: text.length,
        rect: word.rect,
      };
    });

    const matches = [...text.matchAll(pattern)].map((match) => ({
      index: match.index,
      length: match[0].length,
    }));

    if (matches.length === 0 && allowed > 0) {
      for (let first = 0; first + size <= spans.length; first++) {
        const window = spans.slice(first, first + size);
        const candidate = text.slice(window[0].start, window[size - 1].end);

        const normalized = caseSensitive ? candidate : candidate.toLowerCase();
        const prefix = normalized.slice(0, needle.length);

        if (
          editDistance(normalized, needle) <= allowed ||
          editDistance(prefix, needle) <= allowed
        ) {
          matches.push({ index: window[0].start, length: candidate.length });
        }
      }
    }

    for (const match of matches) {
      const end = match.index + match.length;
      const covered = spans.filter(
        (span) => span.start < end && span.end > match.index,
      );

      if (covered.length === 0) {
        continue;
      }

      results.push({
        rect: [
          Math.min(...covered.map((span) => span.rect[0])),
          Math.min(...covered.map((span) => span.rect[1])),
          Math.max(...covered.map((span) => span.rect[2])),
          Math.max(...covered.map((span) => span.rect[3])),
        ],
        snippet: text.slice(Math.max(0, match.index - 24), end + 40).trim(),
      });
    }
  }

  return results;
}

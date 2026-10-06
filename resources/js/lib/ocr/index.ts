import type { PDFPageProxy } from 'pdfjs-dist';
import { recognizePage } from './recognize';
import type { OcrLine } from './types';

export { findOcrMatches } from './match';
export { terminateOcrWorker } from './worker';
export type { OcrLine, OcrMatch, OcrWord, Rect } from './types';

/** Per-document OCR cache keyed by page number. */
export function createOcrCache() {
  const cache = new Map<number, OcrLine[]>();

  return {
    async get(number: number, source: PDFPageProxy): Promise<OcrLine[]> {
      const cached = cache.get(number);

      if (cached) {
        return cached;
      }

      const lines = await recognizePage(source);
      cache.set(number, lines);

      return lines;
    },
    clear(): void {
      cache.clear();
    },
  };
}

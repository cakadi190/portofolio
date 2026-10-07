import type { PDFPageProxy } from 'pdfjs-dist';
import { recognizePage } from './recognize';
import type { OcrLine } from './types';

export { findOcrMatches } from './match';
export { OCR_POOL_SIZE, terminateOcrWorker } from './worker';
export type { OcrLine, OcrMatch, OcrWord, Rect } from './types';

/** Per-document OCR cache keyed by page number. */
export function createOcrCache() {
  const cache = new Map<number, Promise<OcrLine[]>>();

  return {
    /** Shares one in-flight job per page, so prefetching and searching never OCR the same page twice. */
    get(number: number, source: PDFPageProxy): Promise<OcrLine[]> {
      let pending = cache.get(number);

      if (!pending) {
        pending = recognizePage(source).catch((error: unknown) => {
          cache.delete(number);
          throw error;
        });
        cache.set(number, pending);
      }

      return pending;
    },
    clear(): void {
      cache.clear();
    },
  };
}

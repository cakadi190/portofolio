import type { PDFPageProxy } from 'pdfjs-dist';
import { renderPageForOcr } from './render';
import type { OcrLine, Rect } from './types';
import { getOcrScheduler } from './worker';

/** Recognizes a page without a text layer (scanned PDF) and returns word boxes in PDF units. */
export async function recognizePage(source: PDFPageProxy): Promise<OcrLine[]> {
  const { canvas, scale } = await renderPageForOcr(source);
  const pool = await getOcrScheduler();
  const { data } = await pool.addJob('recognize', canvas, {}, { blocks: true });
  canvas.width = 0;
  canvas.height = 0;
  const [left, , , top] = source.view;
  const lines: OcrLine[] = [];

  for (const block of data.blocks ?? []) {
    for (const paragraph of block.paragraphs) {
      for (const line of paragraph.lines) {
        lines.push(
          line.words.map((word) => ({
            text: word.text,
            rect: [
              left + word.bbox.x0 / scale,
              top - word.bbox.y1 / scale,
              left + word.bbox.x1 / scale,
              top - word.bbox.y0 / scale,
            ] as Rect,
          })),
        );
      }
    }
  }

  return lines;
}

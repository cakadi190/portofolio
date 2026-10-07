import type { PDFPageProxy } from 'pdfjs-dist';
import { renderPageForOcr } from './render';
import type { OcrLine, OcrWord, Rect } from './types';
import { getOcrScheduler } from './worker';

/** Horizontal bands (as fractions of page height) re-scanned because full-page segmentation skips text on busy backgrounds. */
const BANDS: Array<[number, number]> = [
  [0, 0.4],
  [0.2, 0.6],
  [0.4, 0.8],
  [0.6, 1],
];

function overlapRatio(a: Rect, b: Rect): number {
  const width = Math.min(a[2], b[2]) - Math.max(a[0], b[0]);
  const height = Math.min(a[3], b[3]) - Math.max(a[1], b[1]);

  if (width <= 0 || height <= 0) {
    return 0;
  }

  return (
    (width * height) /
    Math.min((a[2] - a[0]) * (a[3] - a[1]), (b[2] - b[0]) * (b[3] - b[1]))
  );
}

/** Recognizes a page without a text layer (scanned PDF) and returns word boxes in PDF units. */
export async function recognizePage(source: PDFPageProxy): Promise<OcrLine[]> {
  const { canvas, scale } = await renderPageForOcr(source);
  const pool = await getOcrScheduler();
  const rectangles = [
    undefined,
    ...BANDS.map(([from, to]) => ({
      left: 0,
      top: Math.floor(canvas.height * from),
      width: canvas.width,
      height: Math.ceil(canvas.height * (to - from)),
    })),
  ];
  const results = await Promise.all(
    rectangles.map((rectangle) =>
      pool.addJob('recognize', canvas, rectangle ? { rectangle } : {}, {
        blocks: true,
      }),
    ),
  );
  canvas.width = 0;
  canvas.height = 0;
  const [left, , , top] = source.view;
  const lines: OcrLine[] = [];
  const seen: Rect[] = [];

  for (const { data } of results) {
    for (const block of data.blocks ?? []) {
      for (const paragraph of block.paragraphs) {
        for (const line of paragraph.lines) {
          const words: OcrWord[] = [];

          for (const word of line.words) {
            const rect: Rect = [
              left + word.bbox.x0 / scale,
              top - word.bbox.y1 / scale,
              left + word.bbox.x1 / scale,
              top - word.bbox.y0 / scale,
            ];

            if (
              word.text.trim() === '' ||
              seen.some((other) => overlapRatio(other, rect) > 0.5)
            ) {
              continue;
            }

            seen.push(rect);
            words.push({ text: word.text, rect });
          }

          if (words.length > 0) {
            lines.push(words);
          }
        }
      }
    }
  }

  return lines;
}

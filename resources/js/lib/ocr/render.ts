import type { PDFPageProxy } from 'pdfjs-dist';

const MAX_SCALE = 2.5;
const MAX_SIDE = 3000;

/** Renders a PDF page to a grayscale, high-contrast canvas suited for OCR. */
export async function renderPageForOcr(
  source: PDFPageProxy,
): Promise<{ canvas: HTMLCanvasElement; scale: number }> {
  const base = source.getViewport({ scale: 1, rotation: 0 });
  const scale = Math.min(
    MAX_SCALE,
    MAX_SIDE / Math.max(base.width, base.height),
  );
  const view = source.getViewport({ scale, rotation: 0 });
  const raw = document.createElement('canvas');
  raw.width = Math.ceil(view.width);
  raw.height = Math.ceil(view.height);
  await source.render({ canvas: raw, viewport: view }).promise;

  const canvas = document.createElement('canvas');
  canvas.width = raw.width;
  canvas.height = raw.height;
  const context = canvas.getContext('2d');

  if (context) {
    context.fillStyle = '#fff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.filter = 'grayscale(1) contrast(1.6)';
    context.drawImage(raw, 0, 0);
    raw.width = 0;
    raw.height = 0;
  }

  return { canvas, scale };
}

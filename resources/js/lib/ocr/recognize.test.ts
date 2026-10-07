import { describe, expect, it, vi } from 'vitest';

const addJob = vi.hoisted(() => vi.fn());

vi.mock('@/lib/ocr/worker', () => ({
  getOcrScheduler: async () => ({ addJob }),
}));
vi.mock('@/lib/ocr/render', () => ({
  renderPageForOcr: async () => ({
    canvas: { width: 100, height: 200 },
    scale: 2,
  }),
}));

import { recognizePage } from '@/lib/ocr/recognize';

const word = (
  text: string,
  x0: number,
  y0: number,
  x1: number,
  y1: number,
) => ({
  text,
  bbox: { x0, y0, x1, y1 },
});

describe('recognizePage', () => {
  it('converts word boxes to PDF units and drops blanks and duplicates', async () => {
    const data = {
      blocks: [
        {
          paragraphs: [
            {
              lines: [
                { words: [word('Hi', 0, 20, 20, 40), word(' ', 0, 0, 1, 1)] },
              ],
            },
          ],
        },
      ],
    };
    addJob.mockResolvedValue({ data });

    const lines = await recognizePage({ view: [0, 0, 50, 100] } as never);

    // Full page + 4 bands are scanned; identical boxes are deduplicated.
    expect(addJob).toHaveBeenCalledTimes(5);
    expect(lines).toEqual([[{ text: 'Hi', rect: [0, 80, 10, 90] }]]);
  });

  it('tolerates results without blocks', async () => {
    addJob.mockResolvedValue({ data: {} });

    expect(await recognizePage({ view: [0, 0, 50, 100] } as never)).toEqual([]);
  });
});

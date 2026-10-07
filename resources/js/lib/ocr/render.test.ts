import { describe, expect, it, vi } from 'vitest';
import { renderPageForOcr } from '@/lib/ocr/render';

describe('renderPageForOcr', () => {
  it('caps the scale and renders the page onto a canvas', async () => {
    const render = vi.fn(() => ({ promise: Promise.resolve() }));
    const source = {
      getViewport: ({ scale }: { scale: number }) => ({
        width: 600 * scale,
        height: 800 * scale,
      }),
      render,
    };

    const { canvas, scale } = await renderPageForOcr(source as never);

    expect(scale).toBe(2.5);
    expect(canvas.width).toBe(1500);
    expect(canvas.height).toBe(2000);
    expect(render).toHaveBeenCalledOnce();
  });

  it('limits very large pages by max side length', async () => {
    const source = {
      getViewport: ({ scale }: { scale: number }) => ({
        width: 6000 * scale,
        height: 3000 * scale,
      }),
      render: () => ({ promise: Promise.resolve() }),
    };

    const { scale } = await renderPageForOcr(source as never);

    expect(scale).toBe(0.5);
  });
});

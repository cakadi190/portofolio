import { afterEach, describe, expect, it, vi } from 'vitest';

const toast = vi.hoisted(() => ({ success: vi.fn(), error: vi.fn() }));

vi.mock('@/lib/toast', () => ({ toast }));

import {
  formatBytes,
  MAX_UPLOAD_SIDE,
  shrinkLargeImage,
  uploadMedia,
} from '@/lib/media';

const file = new File(['x'], 'foto.png', { type: 'image/png' });

afterEach(() => {
  document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
});

describe('formatBytes', () => {
  it.each([
    [512, '512 B'],
    [2048, '2 KB'],
    [5 * 1024 * 1024, '5.0 MB'],
  ])('formats %i bytes', (bytes, label) => {
    expect(formatBytes(bytes)).toBe(label);
  });
});

describe('uploadMedia', () => {
  it('posts the file with the XSRF token and returns the item', async () => {
    document.cookie = 'XSRF-TOKEN=a%3Db';
    const item = { id: 1 };
    const fetchMock = vi
      .fn()
      .mockResolvedValue({ ok: true, json: async () => item });
    vi.stubGlobal('fetch', fetchMock);

    await expect(uploadMedia(file)).resolves.toBe(item);

    const [, init] = fetchMock.mock.calls[0];
    expect(init.method).toBe('POST');
    expect(init.headers['X-XSRF-TOKEN']).toBe('a=b');
    expect((init.body as FormData).get('file')).toBeInstanceOf(File);
    expect(toast.success).toHaveBeenCalledWith('foto.png berhasil diunggah.');
  });

  it('surfaces the first validation error', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: false,
        json: async () => ({ errors: { file: ['Terlalu besar'] } }),
      }),
    );

    await expect(uploadMedia(file)).rejects.toThrow('Terlalu besar');
    expect(toast.error).toHaveBeenCalledWith('Terlalu besar');
  });

  it('uses a generic message when the error body is unreadable', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: false,
        json: async () => {
          throw new Error('bad json');
        },
      }),
    );

    await expect(uploadMedia(file)).rejects.toThrow(/Unggahan gagal/);
  });

  it.each([
    [413, /terlalu besar untuk server/],
    [500, /Server gagal memproses/],
  ])(
    'explains a %i response instead of a generic failure',
    async (status, message) => {
      vi.stubGlobal(
        'fetch',
        vi
          .fn()
          .mockResolvedValue({ ok: false, status, json: async () => ({}) }),
      );

      await expect(uploadMedia(file)).rejects.toThrow(message);
    },
  );
});

describe('shrinkLargeImage', () => {
  const bitmap = (width: number, height: number) => ({
    width,
    height,
    close: vi.fn(),
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('leaves small images and non-raster files untouched', async () => {
    vi.stubGlobal('createImageBitmap', vi.fn().mockResolvedValue(bitmap(1600, 900)));

    expect(await shrinkLargeImage(file)).toBe(file);

    const svg = new File(['<svg/>'], 'logo.svg', { type: 'image/svg+xml' });
    const pdf = new File(['%PDF'], 'cv.pdf', { type: 'application/pdf' });

    expect(await shrinkLargeImage(svg)).toBe(svg);
    expect(await shrinkLargeImage(pdf)).toBe(pdf);
  });

  it('scales an oversized image down to the longest-side cap and re-encodes it as webp', async () => {
    vi.stubGlobal('createImageBitmap', vi.fn().mockResolvedValue(bitmap(8000, 4500)));
    const toBlob = vi
      .spyOn(HTMLCanvasElement.prototype, 'toBlob')
      .mockImplementation((callback) => callback(new Blob(['webp'], { type: 'image/webp' })));
    const canvases: HTMLCanvasElement[] = [];
    const createElement = document.createElement.bind(document);
    vi.spyOn(document, 'createElement').mockImplementation((tag: string) => {
      const element = createElement(tag);
      if (tag === 'canvas') {
        canvases.push(element as HTMLCanvasElement);
      }

      return element;
    });

    const result = await shrinkLargeImage(
      new File(['x'], 'Poster Besar.png', { type: 'image/png' }),
    );

    expect(canvases[0].width).toBe(MAX_UPLOAD_SIDE);
    expect(canvases[0].height).toBe(Math.round(4500 * (MAX_UPLOAD_SIDE / 8000)));
    expect(toBlob).toHaveBeenCalledWith(expect.any(Function), 'image/webp', 0.9);
    expect(result.name).toBe('Poster Besar.webp');
    expect(result.type).toBe('image/webp');
  });

  it('falls back to the original file when the image cannot be decoded', async () => {
    vi.stubGlobal('createImageBitmap', vi.fn().mockRejectedValue(new Error('decode')));

    expect(await shrinkLargeImage(file)).toBe(file);
  });
});

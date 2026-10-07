import { afterEach, describe, expect, it, vi } from 'vitest';

const toast = vi.hoisted(() => ({ success: vi.fn(), error: vi.fn() }));

vi.mock('@/lib/toast', () => ({ toast }));

import { formatBytes, uploadMedia } from '@/lib/media';

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
});

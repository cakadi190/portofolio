import { describe, expect, it, vi } from 'vitest';

const recognizePage = vi.hoisted(() => vi.fn());

vi.mock('@/lib/ocr/recognize', () => ({ recognizePage }));

import { createOcrCache } from '@/lib/ocr';

const page = {} as never;

describe('createOcrCache', () => {
  it('shares one job per page number', async () => {
    recognizePage.mockResolvedValue([[]]);
    const cache = createOcrCache();

    const first = cache.get(1, page);
    const second = cache.get(1, page);

    expect(first).toBe(second);
    await first;
    expect(recognizePage).toHaveBeenCalledOnce();
  });

  it('evicts failed jobs so they can be retried', async () => {
    recognizePage.mockRejectedValueOnce(new Error('boom'));
    const cache = createOcrCache();

    await expect(cache.get(2, page)).rejects.toThrow('boom');

    recognizePage.mockResolvedValueOnce([]);
    await expect(cache.get(2, page)).resolves.toEqual([]);
  });

  it('clear() drops cached pages', async () => {
    recognizePage.mockResolvedValue([]);
    const cache = createOcrCache();

    await cache.get(3, page);
    cache.clear();
    await cache.get(3, page);

    expect(recognizePage).toHaveBeenCalledTimes(2);
  });
});

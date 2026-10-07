import { describe, expect, it, vi } from 'vitest';

const pool = vi.hoisted(() => ({ addWorker: vi.fn(), terminate: vi.fn() }));
const worker = vi.hoisted(() => ({ setParameters: vi.fn() }));
const createWorker = vi.hoisted(() => vi.fn());

vi.mock('tesseract.js', () => ({
  createScheduler: () => pool,
  createWorker,
  OEM: { LSTM_ONLY: 1 },
}));

import {
  OCR_POOL_SIZE,
  getOcrScheduler,
  terminateOcrWorker,
} from '@/lib/ocr/worker';

describe('ocr worker pool', () => {
  it('keeps the pool between 1 and 3 workers', () => {
    expect(OCR_POOL_SIZE).toBeGreaterThanOrEqual(1);
    expect(OCR_POOL_SIZE).toBeLessThanOrEqual(3);
  });

  it('builds the pool once and terminates it', async () => {
    createWorker.mockResolvedValue(worker);

    const first = await getOcrScheduler();
    const second = await getOcrScheduler();

    expect(first).toBe(second);
    expect(createWorker).toHaveBeenCalledTimes(OCR_POOL_SIZE);
    expect(createWorker).toHaveBeenCalledWith('eng+ind', 1);
    expect(pool.addWorker).toHaveBeenCalledTimes(OCR_POOL_SIZE);

    terminateOcrWorker();
    await vi.waitFor(() => expect(pool.terminate).toHaveBeenCalledOnce());
  });
});

import type { Scheduler } from 'tesseract.js';

let scheduler: Promise<Scheduler> | null = null;

/** Workers are CPU-bound and each holds the language model in memory, so keep the pool small. */
export const OCR_POOL_SIZE = Math.max(
  1,
  Math.min(
    3,
    (typeof navigator === 'undefined'
      ? 2
      : navigator.hardwareConcurrency || 2) - 1,
  ),
);

/**
 * Lazily builds a pool of workers sharing one job queue. The LSTM-only engine skips the legacy
 * recognizer (faster, smaller), and the page segmentation mode is set once per worker instead of per page.
 */
export function getOcrScheduler(): Promise<Scheduler> {
  scheduler ??= import('tesseract.js').then(
    async ({ createScheduler, createWorker, OEM }) => {
      const pool = createScheduler();
      const workers = await Promise.all(
        Array.from({ length: OCR_POOL_SIZE }, async () => {
          const worker = await createWorker('eng+ind', OEM.LSTM_ONLY);
          await worker.setParameters({
            tessedit_pageseg_mode: '11' as never,
            preserve_interword_spaces: '1',
          });

          return worker;
        }),
      );
      workers.forEach((worker) => pool.addWorker(worker));

      return pool;
    },
  );

  return scheduler;
}

export function terminateOcrWorker(): void {
  void scheduler?.then((pool) => pool.terminate());
  scheduler = null;
}

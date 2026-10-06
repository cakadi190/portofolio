import type { Worker } from 'tesseract.js';

let worker: Promise<Worker> | null = null;

export function getOcrWorker(): Promise<Worker> {
  worker ??= import('tesseract.js').then(({ createWorker }) => createWorker('eng+ind'));

  return worker;
}

export function terminateOcrWorker(): void {
  void worker?.then((instance) => instance.terminate());
  worker = null;
}

import { describe, expect, it, vi } from 'vitest';

const handlers = vi.hoisted(
  () => ({}) as Record<string, (e?: unknown) => void>,
);
const toast = vi.hoisted(() => ({
  success: vi.fn(),
  error: vi.fn(),
  warning: vi.fn(),
  info: vi.fn(),
}));

vi.mock('@inertiajs/svelte', () => ({
  router: {
    on: (name: string, cb: (e?: unknown) => void) => (handlers[name] = cb),
  },
}));
vi.mock('@/lib/toast', () => ({ toast }));

import { initializeFlashToast } from '@/lib/flash-toast';

const event = (detail: unknown) => new CustomEvent('x', { detail });

describe('initializeFlashToast', () => {
  initializeFlashToast();

  it('shows server flash toasts by type', () => {
    handlers.flash(
      event({ flash: { toast: { type: 'success', message: 'OK' } } }),
    );

    expect(toast.success).toHaveBeenCalledWith('OK');
  });

  it('ignores flashes without a toast', () => {
    handlers.flash(event({ flash: {} }));

    expect(toast.success).not.toHaveBeenCalled();
  });

  it('reports validation errors', () => {
    handlers.error();

    expect(toast.error).toHaveBeenCalledWith(
      'Data belum valid, periksa kembali isian Anda.',
    );
  });

  it.each([
    [419, 'warning', 'Sesi kedaluwarsa, muat ulang halaman lalu coba lagi.'],
    [403, 'error', 'Anda tidak memiliki izin untuk aksi ini.'],
    [429, 'warning', 'Terlalu banyak permintaan, coba lagi sebentar lagi.'],
    [500, 'error', 'Terjadi kesalahan pada server. Silakan coba lagi.'],
  ] as const)('maps HTTP %s', (status, type, message) => {
    handlers.httpException(event({ response: { status } }));

    expect(toast[type]).toHaveBeenCalledWith(message);
  });

  it('reports lost connections', () => {
    handlers.networkError();

    expect(toast.error).toHaveBeenCalledWith(
      'Koneksi bermasalah. Periksa jaringan Anda.',
    );
  });
});

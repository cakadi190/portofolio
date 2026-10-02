import { router } from '@inertiajs/svelte';
import { toast } from '@/lib/toast';
import type { FlashToast } from '@/types/ui';

/**
 * Surfaces the outcome of every Inertia request as a toast: server flash
 * messages (success/info/warning/error), validation failures, server errors
 * and lost connections.
 */
export function initializeFlashToast(): void {
  router.on('flash', (event) => {
    const data = (event as CustomEvent).detail?.flash?.toast as
      | FlashToast
      | undefined;

    if (data) {
      toast[data.type](data.message);
    }
  });

  router.on('error', () => {
    toast.error('Data belum valid, periksa kembali isian Anda.');
  });

  router.on('httpException', (event) => {
    const status = (event as CustomEvent).detail?.response?.status;

    if (status === 419) {
      toast.warning('Sesi kedaluwarsa, muat ulang halaman lalu coba lagi.');
    } else if (status === 403) {
      toast.error('Anda tidak memiliki izin untuk aksi ini.');
    } else if (status === 429) {
      toast.warning('Terlalu banyak permintaan, coba lagi sebentar lagi.');
    } else {
      toast.error('Terjadi kesalahan pada server. Silakan coba lagi.');
    }
  });

  router.on('networkError', () => {
    toast.error('Koneksi bermasalah. Periksa jaringan Anda.');
  });
}

import { t } from '@/lib/i18n';
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
      const type = data.type === 'danger' ? 'error' : data.type;

      (toast[type] ?? toast.info)(data.message);
    }
  });

  router.on('error', () => {
    toast.error(t('ui.toast_invalid'));
  });

  router.on('httpException', (event) => {
    const status = (event as CustomEvent).detail?.response?.status;

    if (status === 419) {
      toast.warning(t('ui.toast_session'));
    } else if (status === 403) {
      toast.error(t('ui.toast_forbidden'));
    } else if (status === 429) {
      toast.warning(t('ui.toast_throttled'));
    } else {
      toast.error(t('ui.toast_server'));
    }
  });

  router.on('networkError', () => {
    toast.error(t('ui.toast_network'));
  });
}

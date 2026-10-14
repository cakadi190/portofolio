import toastLib from 'svelte-french-toast';

export type ToastType = 'success' | 'info' | 'warning' | 'error';

const DURATION = 4000;

function notify(icon: string, message: string): void {
  toastLib(message, { icon, duration: DURATION });
}

export const toast = {
  success: (message: string): void => {
    toastLib.success(message, { duration: DURATION });
  },
  error: (message: string): void => {
    toastLib.error(message, { duration: DURATION });
  },
  warning: (message: string): void => notify('⚠️', message),
  info: (message: string): void => notify('ℹ️', message),
  cancelled: (message = 'Aksi dibatalkan.'): void => notify('ℹ️', message),
};

import Swal from 'sweetalert2';
import type { SweetAlertIcon } from 'sweetalert2';

export type ToastType = 'success' | 'info' | 'warning' | 'error';

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  showCloseButton: true,
  timer: 4000,
  timerProgressBar: true,
  didOpen: (element) => {
    element.addEventListener('mouseenter', Swal.stopTimer);
    element.addEventListener('mouseleave', Swal.resumeTimer);
  },
});

function show(icon: SweetAlertIcon, message: string): void {
  void Toast.fire({ icon, title: message });
}

export const toast = {
  success: (message: string): void => show('success', message),
  error: (message: string): void => show('error', message),
  warning: (message: string): void => show('warning', message),
  info: (message: string): void => show('info', message),
  cancelled: (message = 'Aksi dibatalkan.'): void => show('info', message),
};

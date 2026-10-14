export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
  type: 'success' | 'info' | 'warning' | 'error' | 'danger';
  message: string;
};

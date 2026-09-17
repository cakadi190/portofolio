import { createInertiaApp } from '@inertiajs/svelte';
import AppLayout from '@/layouts/app-layout.svelte';
import AuthLayout from '@/layouts/auth-layout.svelte';
import { initDropdownAnimation } from '@/lib/dropdown-animation';
import { initializeFlashToast } from '@/lib/flash-toast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
  title: (title) => (title ? `${title} • ${appName}` : appName),
  layout: (name) => {
    switch (true) {
      case name.startsWith('auth/'):
        return AuthLayout;
      default:
        return AppLayout;
    }
  },
  progress: {
    color: '#4B5563',
  },
});

if (typeof document !== 'undefined') {
  // Bootstrap's JS bundle touches `document` at import time, so it must
  // only load in the browser, never during SSR.
  void import('bootstrap');

  // Layers a shadcn/popover-style fade + zoom on top of Bootstrap's dropdown
  // show/hide lifecycle for every `.dropdown-menu` on the page.
  initDropdownAnimation();

  // This will listen for flash toast data from the server...
  initializeFlashToast();
}

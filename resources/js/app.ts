import { addCollection } from '@iconify/svelte';
import { createInertiaApp, router } from '@inertiajs/svelte';
import AdminLayout from '@/layouts/admin-layout.svelte';
import AppLayout from '@/layouts/app-layout.svelte';
import AuthLayout from '@/layouts/auth-layout.svelte';
import ResumeLayout from '@/layouts/resume-layout.svelte';
import { initializeAnalytics } from '@/lib/analytics';
import { initDropdownAnimation } from '@/lib/dropdown-animation';
import { initializeFlashToast } from '@/lib/flash-toast';
import { iconCollections } from '@/lib/icon-collections';

iconCollections.forEach((collection) => addCollection(collection));

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// The server-rendered HTML must not play the reveal animations itself: Svelte
// hydration would restart them, so they run once, right after hydration.
const markMotionReady = () =>
  window.requestAnimationFrame(() =>
    document.documentElement.classList.add('motion-ready'),
  );

void Promise.resolve(
  createInertiaApp({
    id: 'root',
    title: (title) => (title ? `${title} • ${appName}` : appName),
    layout: (name) => {
      switch (true) {
        case name.startsWith('auth/'):
          return AuthLayout;
        case name.startsWith('resume/') || name === 'certification/show':
          return ResumeLayout;
        case name.startsWith('admin/'):
        case name.startsWith('dashboard/'):
          return AdminLayout;
        default:
          return AppLayout;
      }
    },
    progress: {
      color: '#ff9f1c',
      showSpinner: true,
      includeCSS: true,
    },
  }),
).then(() => {
  if (typeof document !== 'undefined') {
    markMotionReady();
  }
});

if (typeof document !== 'undefined') {
  // Admin CSS is a separate chunk (public visitors never download it). Start
  // fetching it the moment a visit towards the admin begins, so it is applied
  // before the first admin render instead of after mount (FOUC).
  router.on('before', (event) => {
    if (/^\/(admin|dashboard)(\/|$)/.test(event.detail.visit.url.pathname)) {
      void import('../css/admin.scss');
    }
  });

  // Bootstrap's JS bundle touches `document` at import time, so it must
  // only load in the browser, never during SSR.
  void import('bootstrap');

  // Layers a shadcn/popover-style fade + zoom on top of Bootstrap's dropdown
  // show/hide lifecycle for every `.dropdown-menu` on the page.
  initDropdownAnimation();

  // This will listen for flash toast data from the server...
  initializeFlashToast();

  // Error and performance monitoring (Sentry), configured from system settings.
  // The SDK is a separate chunk fetched on the first interaction, or 10 s after
  // the page has loaded for passive readers, keeping it out of LCP/TBT.
  const interactionEvents = ['pointerdown', 'keydown', 'scroll', 'touchstart'];
  let monitoringStarted = false;

  const startMonitoring = () => {
    if (monitoringStarted) {
      return;
    }

    monitoringStarted = true;
    interactionEvents.forEach((name) =>
      window.removeEventListener(name, startMonitoring),
    );
    void import('@/lib/sentry').then(({ initializeSentry }) =>
      initializeSentry(),
    );
  };

  interactionEvents.forEach((name) =>
    window.addEventListener(name, startMonitoring, {
      once: true,
      passive: true,
    }),
  );

  if (document.readyState === 'complete') {
    window.setTimeout(startMonitoring, 10000);
  } else {
    window.addEventListener(
      'load',
      () => window.setTimeout(startMonitoring, 10000),
      { once: true },
    );
  }

  // Google Analytics: SPA page views plus click, form and scroll events.
  initializeAnalytics();
}

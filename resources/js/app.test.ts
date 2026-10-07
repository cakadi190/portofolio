import { beforeAll, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
  createInertiaApp: vi.fn(),
  initializeAnalytics: vi.fn(),
  initDropdownAnimation: vi.fn(),
  initializeFlashToast: vi.fn(),
}));

vi.mock('@inertiajs/svelte', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@inertiajs/svelte')>()),
  createInertiaApp: mocks.createInertiaApp,
}));
vi.mock('@/lib/analytics', () => ({
  initializeAnalytics: mocks.initializeAnalytics,
}));
vi.mock('@/lib/dropdown-animation', () => ({
  initDropdownAnimation: mocks.initDropdownAnimation,
}));
vi.mock('@/lib/flash-toast', () => ({
  initializeFlashToast: mocks.initializeFlashToast,
}));
vi.mock('flag-icons/css/flag-icons.min.css', () => ({}));

import AdminLayout from '@/layouts/admin-layout.svelte';
import AppLayout from '@/layouts/app-layout.svelte';
import AuthLayout from '@/layouts/auth-layout.svelte';

type Config = {
  title: (title: string) => string;
  layout: (name: string) => unknown;
  progress: { color: string };
};

let config: Config;
let callCounts: Record<string, number>;

beforeAll(async () => {
  await import('@/app');
  config = mocks.createInertiaApp.mock.calls[0][0] as Config;
  // `restoreMocks` wipes call history between tests, so snapshot it here.
  callCounts = Object.fromEntries(
    Object.entries(mocks).map(([name, mock]) => [name, mock.mock.calls.length]),
  );
});

describe('app bootstrap', () => {
  it('creates the Inertia app once', () => {
    expect(callCounts.createInertiaApp).toBe(1);
  });

  it.each([
    ['auth/login/login', AuthLayout],
    ['admin/tags/index', AdminLayout],
    ['dashboard/index', AdminLayout],
    ['blog/index', AppLayout],
    ['app/index', AppLayout],
  ])('picks the layout for %s', (page, layout) => {
    expect(config.layout(page)).toBe(layout);
  });

  it('suffixes page titles with the app name', () => {
    expect(config.title('Blog')).toMatch(/^Blog • .+/);
    expect(config.title('')).not.toContain('•');
  });

  it('wires browser-only features once in the browser', () => {
    expect(callCounts.initDropdownAnimation).toBe(1);
    expect(callCounts.initializeFlashToast).toBe(1);
    expect(callCounts.initializeAnalytics).toBe(1);
  });
});

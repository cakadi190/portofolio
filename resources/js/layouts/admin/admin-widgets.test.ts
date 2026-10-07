import { fireEvent, render, screen } from '@testing-library/svelte';
import { afterEach, describe, expect, it, vi } from 'vitest';
import AdminBackToTop from '@/layouts/admin/admin-back-to-top.svelte';
import AdminClock from '@/layouts/admin/admin-clock.svelte';
import AdminFooter from '@/layouts/admin/admin-footer.svelte';
import AdminFullscreenToggle from '@/layouts/admin/admin-fullscreen-toggle.svelte';
import AdminLanguageSwitcher from '@/layouts/admin/admin-language-switcher.svelte';
import AdminNotificationBell from '@/layouts/admin/admin-notification-bell.svelte';
import AdminUserMenu from '@/layouts/admin/admin-user-menu.svelte';
import { hrefOf } from '@/tests/helpers';

afterEach(() => {
  vi.useRealTimers();
});

describe('AdminBackToTop', () => {
  it('shows after scrolling the container and scrolls it to top', async () => {
    const scroller = document.createElement('div');
    const scrollTo = vi.fn();
    scroller.scrollTo = scrollTo as never;
    render(AdminBackToTop, { scroller, bottom: '5rem' });
    const button = screen.getByLabelText('Back to top', { selector: 'button' });

    expect(button.hasAttribute('hidden')).toBe(true);

    scroller.scrollTop = 500;
    await fireEvent.scroll(scroller);
    await vi.waitFor(() => expect(button.hasAttribute('hidden')).toBe(false));

    await fireEvent.click(button);

    expect(scrollTo).toHaveBeenCalledWith({
      top: 0,
      behavior: 'smooth',
    });
  });

  it('stays hidden without a scroller', () => {
    render(AdminBackToTop);

    expect(
      screen
        .getByLabelText('Back to top', { selector: 'button' })
        .hasAttribute('hidden'),
    ).toBe(true);
  });
});

describe('AdminClock', () => {
  it('shows the time and ticks every second', async () => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date(2026, 0, 1, 9, 5, 7));
    render(AdminClock);

    expect(screen.getByText('09:05:07')).toBeTruthy();

    vi.advanceTimersByTime(1000);

    await vi.waitFor(() => expect(screen.getByText('09:05:08')).toBeTruthy());
    expect(document.getElementById('adminClockModal')).not.toBeNull();
  });
});

describe('AdminFooter', () => {
  it('shows the copyright and version badge', () => {
    render(AdminFooter);

    expect(screen.getByText(/Hak Cipta 2026/)).toBeTruthy();
    expect(document.querySelector('.version-badge')?.textContent).toBeTruthy();
  });
});

describe('AdminFullscreenToggle', () => {
  it('hides when fullscreen is unsupported', () => {
    Object.defineProperty(document, 'fullscreenEnabled', {
      value: false,
      configurable: true,
    });
    render(AdminFullscreenToggle);

    expect(screen.queryByRole('button')).toBeNull();
  });

  it('requests fullscreen and follows fullscreenchange', async () => {
    Object.defineProperty(document, 'fullscreenEnabled', {
      value: true,
      configurable: true,
    });
    Object.defineProperty(document, 'fullscreenElement', {
      value: null,
      configurable: true,
    });
    const request = vi.fn().mockResolvedValue(undefined);
    document.documentElement.requestFullscreen = request;
    render(AdminFullscreenToggle);

    await fireEvent.click(screen.getByLabelText('Masuk layar penuh'));
    expect(request).toHaveBeenCalledOnce();

    Object.defineProperty(document, 'fullscreenElement', {
      value: document.body,
      configurable: true,
    });
    document.dispatchEvent(new Event('fullscreenchange'));

    expect(await screen.findByLabelText('Keluar layar penuh')).toBeTruthy();
    Object.defineProperty(document, 'fullscreenElement', {
      value: null,
      configurable: true,
    });
  });
});

describe('AdminLanguageSwitcher', () => {
  it('switches the current locale', async () => {
    render(AdminLanguageSwitcher);

    expect(
      screen.getByLabelText('Pilih bahasa: Bahasa Indonesia', {
        selector: 'button',
      }),
    ).toBeTruthy();

    await fireEvent.click(screen.getByText('Bahasa Inggris'));

    expect(
      screen.getByLabelText('Pilih bahasa: Bahasa Inggris', {
        selector: 'button',
      }),
    ).toBeTruthy();
  });
});

describe('AdminNotificationBell', () => {
  it('shows a badge only when there are notifications', () => {
    const { unmount } = render(AdminNotificationBell, { count: 3 });
    expect(screen.getByText('3')).toBeTruthy();
    unmount();

    render(AdminNotificationBell);
    expect(screen.queryByText('notifikasi belum dibaca')).toBeNull();
    expect(screen.getByText('Belum ada notifikasi.')).toBeTruthy();
  });
});

describe('AdminUserMenu', () => {
  it('renders profile links, name and the logout action', () => {
    render(AdminUserMenu, { userName: 'Adi', userEmail: 'a@b.c' });

    expect(screen.getAllByText('Adi').length).toBeGreaterThan(0);
    expect(hrefOf(screen.getByRole('link', { name: /Profil Saya/ }))).toBe(
      '/settings/profile',
    );
    expect(hrefOf(screen.getByRole('link', { name: /Keamanan/ }))).toBe(
      '/settings/security',
    );
    expect(
      screen.getByText('Keluar', { selector: '.logout-actions' }),
    ).toBeTruthy();
  });

  it('falls back to a dash and shows the avatar when present', () => {
    const { unmount } = render(AdminUserMenu, {});
    expect(screen.getAllByText('—').length).toBeGreaterThan(0);
    unmount();

    render(AdminUserMenu, { userName: 'Adi', userAvatar: 'avatars/a.webp' });
    expect(screen.getAllByAltText('Adi')[0].getAttribute('src')).toBe(
      '/storage/avatars/a.webp',
    );
  });
});

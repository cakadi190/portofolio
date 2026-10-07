import { fireEvent, render, screen } from '@testing-library/svelte';
import { createRawSnippet } from 'svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { adminMenu } from '@/layouts/admin/admin-menu';
import AdminLayout from '@/layouts/admin-layout.svelte';
import AdminSidebarLayout from '@/layouts/admin/admin-sidebar-layout.svelte';
import { hrefOf } from '@/tests/helpers';

const inertia = vi.hoisted(() => ({
  page: {
    url: '/admin/tags',
    props: { auth: { user: { name: 'Adi', email: 'a@b.c', avatar: null } } },
  },
}));

vi.mock('@inertiajs/svelte', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@inertiajs/svelte')>()),
  page: inertia.page,
}));

const content = createRawSnippet(() => ({
  render: () => '<p>Konten halaman</p>',
}));

function mount(url = '/admin/tags') {
  return render(AdminSidebarLayout, {
    menu: adminMenu(url),
    userName: 'Adi',
    userEmail: 'a@b.c',
    children: content,
  });
}

beforeEach(() => {
  window.localStorage.clear();
  document.documentElement.classList.remove('sidebar-toggled');
});

describe('AdminSidebarLayout', () => {
  it('renders the shell: sidebar, navbar, content and footer', () => {
    mount();

    expect(screen.getByLabelText('Sidebar admin')).toBeTruthy();
    expect(screen.getByLabelText('Navigasi atas')).toBeTruthy();
    expect(screen.getByText('Konten halaman')).toBeTruthy();
    expect(
      screen.getByRole('link', { name: 'Langsung ke konten utama' }),
    ).toBeTruthy();
    expect(document.getElementById('admin-main-content')).not.toBeNull();
  });

  it('lists menu sections and the user in the footer', () => {
    mount();

    expect(screen.getByText('Menu Utama')).toBeTruthy();
    expect(hrefOf(screen.getByRole('link', { name: /Dasbor/ }))).toBe('/admin');
    expect(document.querySelector('.userinfo-detail-email')?.textContent).toBe(
      'a@b.c',
    );
  });

  it('reveals the branch holding the active route and marks the link current', () => {
    mount('/admin/tags');

    expect(
      screen
        .getByRole('link', { name: /Tag Artikel/ })
        .getAttribute('aria-current'),
    ).toBe('page');
    expect(
      screen
        .getByRole('button', { name: /Blog/ })
        .getAttribute('aria-expanded'),
    ).toBe('true');
    expect(
      screen
        .getByRole('button', { name: /Portofolio/ })
        .getAttribute('aria-expanded'),
    ).toBe('false');
  });

  it('works as an accordion', async () => {
    mount('/admin/tags');

    await fireEvent.click(screen.getByRole('button', { name: /Portofolio/ }));

    expect(
      screen
        .getByRole('button', { name: /Portofolio/ })
        .getAttribute('aria-expanded'),
    ).toBe('true');
    expect(
      screen
        .getByRole('button', { name: /Blog/ })
        .getAttribute('aria-expanded'),
    ).toBe('false');
  });

  it('filters the menu by search and reports no results', async () => {
    mount();
    const search = screen.getByLabelText('Cari menu');

    await fireEvent.input(search, { target: { value: 'kopi' } });

    expect(screen.getByText('Kedai Kopi')).toBeTruthy();
    expect(screen.queryByText('Pengguna')).toBeNull();

    await fireEvent.input(search, { target: { value: 'zzzz' } });

    expect(screen.getByText('Tidak ada menu yang cocok.')).toBeTruthy();
  });

  it('focuses search with "/" but not while typing in a field', async () => {
    mount();
    const search = screen.getByLabelText('Cari menu');

    await fireEvent.keyDown(document.body, { key: '/' });
    expect(document.activeElement).toBe(search);

    (search as HTMLInputElement).blur();
    const field = document.createElement('input');
    document.body.appendChild(field);
    field.focus();
    await fireEvent.keyDown(field, { key: '/' });
    expect(document.activeElement).toBe(field);
  });

  it('collapses the sidebar from the toggle and persists it', async () => {
    vi.mocked(window.matchMedia).mockImplementation(
      (query: string) =>
        ({
          matches: true,
          media: query,
          addEventListener: vi.fn(),
          removeEventListener: vi.fn(),
        }) as unknown as MediaQueryList,
    );
    mount();

    await fireEvent.click(screen.getAllByLabelText('Buka/tutup sidebar')[0]);

    expect(
      document.querySelector('.admin-shell--sidebar-collapsed'),
    ).not.toBeNull();
    expect(window.localStorage.getItem('sidebar:collapsed')).toBe('1');
  });

  it('opens and closes the mobile drawer', async () => {
    vi.mocked(window.matchMedia).mockImplementation(
      (query: string) =>
        ({
          matches: false,
          media: query,
          addEventListener: vi.fn(),
          removeEventListener: vi.fn(),
        }) as unknown as MediaQueryList,
    );
    mount();

    await fireEvent.click(screen.getAllByLabelText('Buka/tutup sidebar')[0]);
    expect(document.querySelector('.sidebar--mobile-open')).not.toBeNull();

    await fireEvent.keyDown(document.body, { key: 'Escape' });
    expect(document.querySelector('.sidebar--mobile-open')).toBeNull();
  });

  it('shows the signed-in user in the navbar menu', () => {
    mount();

    expect(screen.getAllByText('Adi').length).toBeGreaterThan(0);
  });
});

describe('AdminLayout', () => {
  it('builds the menu from the current page and user', () => {
    render(AdminLayout, { children: content });

    expect(screen.getByText('Konten halaman')).toBeTruthy();
    expect(
      screen
        .getByRole('link', { name: /Tag Artikel/ })
        .getAttribute('aria-current'),
    ).toBe('page');
  });

  it('accepts a custom menu', () => {
    render(AdminLayout, {
      menu: [{ type: 'header', label: 'Kustom' }],
      userName: 'X',
      userEmail: 'x@y.z',
      children: content,
    });

    expect(screen.getByText('Kustom')).toBeTruthy();
    expect(screen.queryByText('Dasbor')).toBeNull();
  });
});

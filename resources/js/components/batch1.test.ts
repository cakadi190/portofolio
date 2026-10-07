import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';
import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
import SettingsAccountNav from '@/components/admin/settings-account-nav.svelte';
import AppBrand from '@/components/app-brand.svelte';
import AppLogoIcon from '@/components/app-logo-icon.svelte';
import BackToTop from '@/components/back-to-top.svelte';
import CardBlog from '@/components/card-blog.svelte';
import ElkuslaSection from '@/components/home/elkusla-section.svelte';
import HeaderPage from '@/components/header-page.svelte';
import LanguageSwitcher from '@/components/language-switcher.svelte';
import SimplePaginator from '@/components/simple-paginator.svelte';
import TextLink from '@/components/text-link.svelte';
import ThemeToggler from '@/components/theme-toggler.svelte';
import { THEME_STORAGE_KEY } from '@/lib/theme';
import { hrefOf } from '@/tests/helpers';

describe('AdminPageHeader', () => {
  it('renders title and subtitle', () => {
    render(AdminPageHeader, { title: 'Artikel', subtitle: 'Kelola artikel' });

    expect(screen.getByRole('heading', { name: 'Artikel' })).toBeTruthy();
    expect(screen.getByText('Kelola artikel')).toBeTruthy();
    expect(screen.queryByText('Tambah')).toBeNull();
  });

  it('renders a create link when createHref is given', () => {
    render(AdminPageHeader, { title: 'A', createHref: '/admin/a/create' });

    expect(hrefOf(screen.getByRole('link', { name: /Tambah/ }))).toBe(
      '/admin/a/create',
    );
  });

  it('prefers a create button and calls onCreate', async () => {
    const onCreate = vi.fn();
    render(AdminPageHeader, {
      title: 'A',
      createHref: '/x',
      createLabel: 'Baru',
      onCreate,
    });

    await fireEvent.click(screen.getByRole('button', { name: /Baru/ }));

    expect(onCreate).toHaveBeenCalledOnce();
    expect(screen.queryByRole('link')).toBeNull();
  });
});

describe('SettingsAccountNav', () => {
  it('marks the current section active', () => {
    render(SettingsAccountNav, { current: 'security' });

    expect(screen.getByRole('link', { name: /Keamanan/ }).classList).toContain(
      'active',
    );
    expect(
      screen.getByRole('link', { name: /Profil/ }).classList,
    ).not.toContain('active');
  });
});

describe('AppBrand', () => {
  it('renders both wordmarks in auto mode', () => {
    const { container } = render(AppBrand);

    expect(container.querySelectorAll('img')).toHaveLength(2);
    expect(hrefOf(screen.getByRole('link'))).toBe('/');
  });

  it('pins the colored wordmark', () => {
    const { container } = render(AppBrand, { variant: 'color' });

    const images = container.querySelectorAll('img');
    expect(images).toHaveLength(1);
    expect(images[0].classList).toContain('logo-fixed');
  });

  it('pins the white wordmark', () => {
    const { container } = render(AppBrand, { variant: 'white' });

    expect(container.querySelector('.logo-light')).toBeNull();
    expect(container.querySelector('.logo-dark')).not.toBeNull();
  });
});

describe('AppLogoIcon', () => {
  it('renders light and dark icons with the given height', () => {
    const { container } = render(AppLogoIcon, { height: 48, class: 'x' });

    expect(container.querySelectorAll('img')).toHaveLength(2);
    expect(container.querySelector('img')?.getAttribute('height')).toBe('48');
    expect(container.querySelector('.app-logo-icon.x')).not.toBeNull();
  });
});

describe('BackToTop', () => {
  it('becomes visible after scrolling and scrolls to top on click', async () => {
    const scrollTo = vi.fn();
    vi.stubGlobal('scrollTo', scrollTo);
    window.scrollTo = scrollTo;
    render(BackToTop);
    const button = screen.getByRole('button', { name: 'Kembali ke atas' });

    expect(button.classList).not.toContain('back-to-top--visible');

    Object.defineProperty(window, 'scrollY', {
      value: 200,
      configurable: true,
    });
    await fireEvent.scroll(window);
    await vi.waitFor(() =>
      expect(button.classList).toContain('back-to-top--visible'),
    );

    await fireEvent.click(button);

    expect(scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' });
    Object.defineProperty(window, 'scrollY', { value: 0, configurable: true });
  });
});

describe('CardBlog', () => {
  const base = {
    title: 'Judul',
    slug: 'judul',
    excerpt: 'Ringkasan',
    coverImage: '/cover.webp',
    categories: [{ name: 'Teknologi', color: '#ff0000' }],
    tags: ['php', 'svelte'],
  };

  it('renders all parts and links to the post', () => {
    render(CardBlog, base);

    expect(hrefOf(screen.getByRole('link'))).toBe('/blog/judul');
    expect(screen.getByText('Ringkasan')).toBeTruthy();
    expect(screen.getByText('Teknologi')).toBeTruthy();
    expect(screen.getByText('php')).toBeTruthy();
    expect(screen.getByRole('img').getAttribute('src')).toBe('/cover.webp');
  });

  it('omits optional parts when absent', () => {
    render(CardBlog, {
      ...base,
      excerpt: null,
      coverImage: null,
      categories: [],
      tags: [],
    });

    expect(screen.queryByRole('img')).toBeNull();
    expect(screen.queryByText('Ringkasan')).toBeNull();
    expect(screen.queryByText('Teknologi')).toBeNull();
  });
});

describe('ElkuslaSection', () => {
  it('renders the quote', () => {
    render(ElkuslaSection);

    expect(screen.getByText('Slamet!')).toBeTruthy();
  });
});

describe('HeaderPage', () => {
  it('renders title and subtitle without a back link', () => {
    render(HeaderPage, { title: 'Blog', subtitle: 'Tulisan' });

    expect(
      screen.getByRole('heading', { level: 1, name: 'Blog' }),
    ).toBeTruthy();
    expect(
      screen.getByRole('heading', { level: 2, name: 'Tulisan' }),
    ).toBeTruthy();
    expect(screen.queryByRole('link')).toBeNull();
  });

  it('renders a back link when backTo is set', () => {
    render(HeaderPage, { title: 'B', subtitle: 'S', backTo: '/blog' });

    expect(hrefOf(screen.getByRole('link', { name: /Kembali/ }))).toBe('/blog');
  });
});

describe('LanguageSwitcher', () => {
  it('lists both languages', () => {
    render(LanguageSwitcher);

    expect(screen.getByText('Bahasa Indonesia')).toBeTruthy();
    expect(screen.getByText('Bahasa Inggris')).toBeTruthy();
  });
});

describe('SimplePaginator', () => {
  it('renders nothing for a single page', () => {
    const { container } = render(SimplePaginator, {
      currentPage: 1,
      lastPage: 1,
      prevPageUrl: null,
      nextPageUrl: null,
    });

    expect(container.querySelector('button, a')).toBeNull();
  });

  it('disables unavailable directions and links available ones', () => {
    render(SimplePaginator, {
      currentPage: 1,
      lastPage: 3,
      prevPageUrl: null,
      nextPageUrl: '/blog?page=2',
    });

    expect(screen.getByText('Halaman 1 dari 3')).toBeTruthy();
    expect(
      (
        screen.getByRole('button', {
          name: 'Halaman sebelumnya',
        }) as HTMLButtonElement
      ).disabled,
    ).toBe(true);
    expect(
      hrefOf(screen.getByRole('link', { name: 'Halaman berikutnya' })),
    ).toBe('/blog?page=2');
  });
});

describe('TextLink', () => {
  it('renders a styled link with children', () => {
    render(TextLink, { href: '/login' });

    const link = screen.getByRole('link');
    expect(hrefOf(link)).toBe('/login');
    expect(link.classList).toContain('text-decoration-underline');
  });
});

describe('ThemeToggler', () => {
  it('toggles the theme attribute and persists it', async () => {
    document.documentElement.setAttribute('data-bs-theme', 'light');
    render(ThemeToggler);

    await fireEvent.click(
      screen.getByRole('button', { name: 'Ubah ke mode gelap' }),
    );

    expect(document.documentElement.getAttribute('data-bs-theme')).toBe('dark');
    expect(window.localStorage.getItem(THEME_STORAGE_KEY)).toBe('dark');
    expect(
      await screen.findByRole('button', { name: 'Ubah ke mode terang' }),
    ).toBeTruthy();
  });

  it('survives blocked storage', async () => {
    document.documentElement.setAttribute('data-bs-theme', 'light');
    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
      throw new Error('blocked');
    });
    render(ThemeToggler);

    await fireEvent.click(screen.getByRole('button'));

    expect(document.documentElement.getAttribute('data-bs-theme')).toBe('dark');
  });
});

import { fireEvent, render, screen } from '@testing-library/svelte';
import { createRawSnippet } from 'svelte';
import { describe, expect, it, vi } from 'vitest';
import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
import SettingsLayout from '@/components/admin/settings-layout.svelte';
import BlogHomeSection from '@/components/home/blog-home-section.svelte';
import PortfolioHomeSection from '@/components/home/portfolio-home-section.svelte';
import CardPortfolio from '@/components/card-portfolio.svelte';
import CoffeeMap from '@/components/coffee-map.svelte';
import FormModal from '@/components/form-modal.svelte';
import LogoutAction from '@/components/logout-action.svelte';
import ModalConfirmation from '@/components/modal-confirmation.svelte';
import SiteFooter from '@/components/site-footer.svelte';
import SiteNavbar from '@/components/site-navbar.svelte';
import { hrefOf } from '@/tests/helpers';

const mocks = vi.hoisted(() => ({ delete: vi.fn(), post: vi.fn() }));

vi.mock('@inertiajs/svelte', async (importOriginal) => {
  const original = await importOriginal<typeof import('@inertiajs/svelte')>();

  return {
    ...original,
    router: Object.assign(Object.create(original.router), {
      delete: mocks.delete,
      post: mocks.post,
    }),
  };
});

const text = (value: string) =>
  createRawSnippet(() => ({ render: () => `<span>${value}</span>` }));

const post = {
  title: 'Judul',
  slug: 'judul',
  excerpt: null,
  coverImage: null,
  categories: [],
  tags: [],
};

const portfolio = {
  name: 'Proyek',
  slug: 'proyek',
  image: '/p.webp',
  shortDesc: 'Deskripsi',
  services: [{ name: 'Web', color: null }],
  technologies: [],
};

describe('ModalConfirmation', () => {
  const props = { title: 'Yakin?', description: 'Tidak bisa dibatalkan.' };

  it('renders title, description and custom labels', () => {
    render(ModalConfirmation, {
      ...props,
      actions: { confirm: vi.fn() },
      confirmLabel: 'Hapus',
      denyLabel: 'Tidak',
    });

    expect(screen.getByText('Yakin?')).toBeTruthy();
    expect(screen.getByText('Tidak bisa dibatalkan.')).toBeTruthy();
    expect(screen.getByText('Hapus', { selector: 'button' })).toBeTruthy();
    expect(screen.getByText('Tidak')).toBeTruthy();
  });

  it('runs the confirm action', async () => {
    const confirm = vi.fn();
    render(ModalConfirmation, { ...props, actions: { confirm } });

    await fireEvent.click(screen.getByText('Ya'));

    expect(confirm).toHaveBeenCalledOnce();
  });

  it('runs the deny action', async () => {
    const deny = vi.fn();
    render(ModalConfirmation, {
      ...props,
      actions: { confirm: vi.fn(), deny },
    });

    await fireEvent.click(screen.getByText('Batal'));

    expect(deny).toHaveBeenCalledOnce();
  });

  it('disables confirm while processing', () => {
    render(ModalConfirmation, {
      ...props,
      actions: { confirm: vi.fn() },
      processing: true,
    });

    expect((screen.getByText('Ya') as HTMLButtonElement).disabled).toBe(true);
  });

  it('shows the Bootstrap modal when open', async () => {
    render(ModalConfirmation, {
      ...props,
      actions: { confirm: vi.fn() },
      open: true,
    });

    await vi.waitFor(() =>
      expect(document.querySelector('.modal-confirmation.show')).not.toBeNull(),
    );
  });
});

describe('FormModal', () => {
  it('renders header, subtitle and body', () => {
    render(FormModal, {
      title: 'Tambah',
      subtitle: 'Isi formulir',
      size: 'lg',
      children: text('isi'),
    });

    expect(screen.getByText('Tambah')).toBeTruthy();
    expect(screen.getByText('Isi formulir')).toBeTruthy();
    expect(screen.getByText('isi')).toBeTruthy();
    expect(document.querySelector('.modal-dialog.modal-lg')).not.toBeNull();
    expect(screen.getByLabelText('Tutup')).toBeTruthy();
  });

  it('shows the Bootstrap modal when open', async () => {
    render(FormModal, { title: 'T', open: true, children: text('x') });

    await vi.waitFor(() =>
      expect(document.querySelector('.modal.show')).not.toBeNull(),
    );
  });
});

describe('AdminDeleteButton', () => {
  it('deletes through the router after confirming', async () => {
    render(AdminDeleteButton, { href: '/admin/posts/1' });

    await fireEvent.click(screen.getByRole('button', { name: /Hapus$/ }));
    const confirm = screen
      .getAllByText('Hapus', { selector: 'button' })
      .at(-1) as HTMLElement;
    await fireEvent.click(confirm);

    expect(mocks.delete).toHaveBeenCalledWith(
      '/admin/posts/1',
      expect.objectContaining({ preserveScroll: true }),
    );
  });
});

describe('LogoutAction', () => {
  it('posts a logout after confirming', async () => {
    render(LogoutAction, { children: text('Keluar akun') });

    await fireEvent.click(screen.getByText('Keluar akun'));
    await fireEvent.click(
      screen.getByText('Keluar', { selector: 'button.btn-danger' }),
    );

    expect(mocks.post).toHaveBeenCalledWith(
      expect.stringContaining('logout'),
      {},
      expect.any(Object),
    );
  });
});

describe('SettingsLayout', () => {
  it('renders nav and content slots', () => {
    render(SettingsLayout, { nav: text('menu'), children: text('konten') });

    expect(screen.getByText('menu')).toBeTruthy();
    expect(screen.getByText('konten')).toBeTruthy();
  });
});

describe('BlogHomeSection', () => {
  it('shows an empty message without posts', () => {
    render(BlogHomeSection);

    expect(screen.getByText(/Belum ada artikel/)).toBeTruthy();
  });

  it('renders a card per post and a link to the blog', () => {
    render(BlogHomeSection, {
      posts: [post, { ...post, slug: 'dua', title: 'Dua' }],
    });

    expect(screen.getByText('Judul')).toBeTruthy();
    expect(screen.getByText('Dua')).toBeTruthy();
    expect(
      hrefOf(screen.getByRole('link', { name: /Lihat Selengkapnya/ })),
    ).toBe('/blog');
  });
});

describe('PortfolioHomeSection', () => {
  it('shows an empty message without portfolios', () => {
    render(PortfolioHomeSection);

    expect(screen.getByText(/Belum ada portofolio/)).toBeTruthy();
  });

  it('renders a card per portfolio', () => {
    render(PortfolioHomeSection, { portfolios: [portfolio] });

    expect(screen.getByText('Proyek')).toBeTruthy();
    expect(
      hrefOf(screen.getByRole('link', { name: /Lihat Selengkapnya/ })),
    ).toBe('/portofolio');
  });
});

describe('CardPortfolio', () => {
  it('renders details and links to the portfolio', () => {
    render(CardPortfolio, portfolio);

    expect(hrefOf(screen.getByRole('link'))).toBe('/portofolio/proyek');
    expect(screen.getByText('Deskripsi')).toBeTruthy();
    expect(screen.getByText('Web')).toBeTruthy();
    expect(screen.getByRole('img').getAttribute('src')).toBe('/p.webp');
  });

  it('omits optional parts', () => {
    render(CardPortfolio, { ...portfolio, shortDesc: null, services: [] });

    expect(screen.queryByText('Deskripsi')).toBeNull();
    expect(screen.queryByText('Web')).toBeNull();
  });
});

describe('CoffeeMap', () => {
  it('renders an accessible map container', () => {
    vi.stubGlobal(
      'ResizeObserver',
      class {
        observe = vi.fn();
        disconnect = vi.fn();
      },
    );

    render(CoffeeMap, { latitude: '-7.4', longitude: 111.4, label: 'Kopi' });

    expect(screen.getByLabelText('Peta lokasi Kopi')).toBeTruthy();
    expect(document.querySelector('.leaflet-container')).not.toBeNull();
  });
});

describe('SiteFooter', () => {
  it('renders the current year, social and nav links', () => {
    render(SiteFooter);

    expect(
      screen.getByText(new RegExp(`${new Date().getFullYear()} Cak Adi`)),
    ).toBeTruthy();
    expect(
      screen.getByLabelText("Cak Adi's Facebook").getAttribute('target'),
    ).toBe('_blank');
    expect(hrefOf(screen.getByRole('link', { name: 'Blog Pribadi' }))).toBe(
      '/blog',
    );
    expect(hrefOf(screen.getByRole('link', { name: 'Karir' }))).toBe('/karir');
  });
});

describe('SiteNavbar', () => {
  it('renders the menu twice (desktop + offcanvas)', () => {
    render(SiteNavbar);

    expect(screen.getAllByRole('link', { name: 'Portofolio' })).toHaveLength(2);
    expect(screen.getByLabelText('Buka navigasi')).toBeTruthy();
  });

  it('styles the navbar once scrolled', async () => {
    render(SiteNavbar);
    const navbar = document.querySelector('nav.navbar') as HTMLElement;

    Object.defineProperty(window, 'scrollY', {
      value: 120,
      configurable: true,
    });
    await fireEvent.scroll(window);

    await vi.waitFor(() => expect(navbar.classList).toContain('border-bottom'));

    Object.defineProperty(window, 'scrollY', { value: 0, configurable: true });
    await fireEvent.scroll(window);

    await vi.waitFor(() =>
      expect(navbar.classList).not.toContain('border-bottom'),
    );
  });
});

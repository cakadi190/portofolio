import { fireEvent, render, screen } from '@testing-library/svelte';
import type { Component } from 'svelte';
import { describe, expect, it, vi } from 'vitest';
import Awards from '@/pages/admin/awards/index.svelte';
import SpeakingEngagements from '@/pages/admin/speaking-engagements/index.svelte';
import Careers from '@/pages/admin/careers/index.svelte';
import ClarificationForm from '@/pages/admin/clarifications/form.svelte';
import Clarifications from '@/pages/admin/clarifications/index.svelte';
import Certifications from '@/pages/admin/certifications/index.svelte';
import DevCommunities from '@/pages/admin/dev-communities/index.svelte';
import ProgrammerChannels from '@/pages/admin/programmer-channels/index.svelte';
import CoffeePlaces from '@/pages/admin/coffee-places/index.svelte';
import ContactMessages from '@/pages/admin/contact-messages/index.svelte';
import Educations from '@/pages/admin/educations/index.svelte';
import MediaLibrary from '@/pages/admin/media/index.svelte';
import Organizations from '@/pages/admin/organizations/index.svelte';
import PortfolioGalleries from '@/pages/admin/portfolio-galleries/index.svelte';
import PortfolioRatings from '@/pages/admin/portfolio-ratings/index.svelte';
import PortfolioForm from '@/pages/admin/portfolios/form.svelte';
import Portfolios from '@/pages/admin/portfolios/index.svelte';
import Services from '@/pages/admin/services/index.svelte';
import PostCategories from '@/pages/admin/post-categories/index.svelte';
import PostForm from '@/pages/admin/posts/form.svelte';
import Posts from '@/pages/admin/posts/index.svelte';
import Profile from '@/pages/admin/settings/profile.svelte';
import Security from '@/pages/admin/settings/security.svelte';
import SystemSettings from '@/pages/admin/system-settings/index.svelte';
import Tags from '@/pages/admin/tags/index.svelte';
import Technologies from '@/pages/admin/technologies/index.svelte';
import Users from '@/pages/admin/users/index.svelte';

vi.mock('@laravel/passkeys/svelte', () => ({
  usePasskeyRegister: () => ({
    isSupported: true,
    isLoading: false,
    error: null,
    register: vi.fn(),
  }),
}));

const empty = {
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
  prev_page_url: null,
  next_page_url: null,
};

const filters = { search: '', sort: null, direction: 'asc', per_page: 10 };
const options = [{ value: 'a', label: 'Opsi A' }];
const idOptions = [{ id: 1, name: 'Opsi 1' }];

type Case = [
  title: string,
  Page: Component<never>,
  props: Record<string, unknown>,
];

const indexPages: Case[] = [
  ['Penghargaan', Awards, { awards: empty, types: options, filters }],
  [
    'Pembicara & Mentoring',
    SpeakingEngagements,
    { speakingEngagements: empty, roles: options, formats: options, filters },
  ],
  ['Riwayat Karier', Careers, { careers: empty, portfolios: [], filters }],
  ['Klarifikasi', Clarifications, { clarifications: empty, filters }],
  ['Sertifikasi', Certifications, { certifications: empty, filters }],
  [
    'Kedai Kopi',
    CoffeePlaces,
    {
      coffeePlaces: empty,
      wifiSpeeds: options,
      priceTiers: options,
      facilities: options,
      regions: [],
      filters,
    },
  ],
  ['Komunitas Dev ID', DevCommunities, { communities: empty, platforms: options, filters }],
  ['Channel Programmer', ProgrammerChannels, { channels: empty, platforms: options, filters }],
  ['Pesan Masuk', ContactMessages, { messages: empty, filters }],
  [
    'Riwayat Pendidikan',
    Educations,
    { educations: empty, levels: options, scoreTypes: options, filters },
  ],
  [
    'Pustaka Media',
    MediaLibrary,
    { media: empty, filters: { search: '', type: 'all' } },
  ],
  ['Pengalaman Organisasi', Organizations, { organizations: empty, filters }],
  [
    'Galeri Portofolio',
    PortfolioGalleries,
    { portfolioGalleries: empty, portfolios: [], filters },
  ],
  [
    'Ulasan Portofolio',
    PortfolioRatings,
    { portfolioRatings: empty, portfolios: [], filters },
  ],
  ['Portofolio', Portfolios, { portfolios: empty, filters }],
  ['Layanan', Services, { services: empty, filters }],
  ['Kategori Artikel', PostCategories, { postCategories: empty, filters }],
  ['Artikel Blog', Posts, { posts: empty, filters, authors: [] }],
  ['Tag', Tags, { tags: empty, filters }],
  ['Teknologi', Technologies, { technologies: empty, filters }],
  [
    'Pengguna',
    Users,
    { users: empty, accountTypes: options, genders: options, filters },
  ],
];

describe.each(indexPages)('admin %s page', (title, Page, props) => {
  it('renders its header, search and empty table', () => {
    render(Page, props as never);

    expect(document.title).toContain(title);
    expect(screen.getByRole('heading', { level: 1, name: title })).toBeTruthy();
    expect(document.querySelector('input[type="search"]')).not.toBeNull();
    expect(document.body.textContent).toMatch(/Tidak ada data|Belum ada/);
  });
});

describe('admin settings pages', () => {
  it('Profile renders account fields', () => {
    render(Profile, {
      profile: {
        name: 'Adi',
        email: 'a@b.c',
        phone: null,
        gender: null,
        avatar: null,
      },
      genders: options,
    });

    expect(document.title).toContain('Profil Saya');
    expect(
      document.querySelector<HTMLInputElement>('input[name="email"]')?.value,
    ).toBe('a@b.c');
    expect(
      document.querySelector<HTMLInputElement>('input[name="name"]')?.value,
    ).toBe('Adi');
  });

  it('Security renders the password form and optional sections', () => {
    render(Security, {
      passwordRules: 'min:8',
      canManagePasskeys: true,
      passkeys: [],
    });

    expect(document.title).toContain('Keamanan Akun');
    expect(screen.getByText('Belum ada passkey')).toBeTruthy();
    expect(
      document.querySelector('input[name="current_password"]'),
    ).not.toBeNull();
  });

  it('SystemSettings renders each group', () => {
    render(SystemSettings, {
      groups: [
        {
          value: 'information',
          label: 'Informasi',
          description: 'Info situs',
          fields: [
            {
              key: 'site_name',
              label: 'Nama Situs',
              type: 'text',
              placeholder: 'Nama',
            },
          ],
        },
      ],
      values: { site_name: 'Catatan' },
    });

    expect(document.title).toContain('Pengaturan Sistem');
    expect(screen.getByRole('tab', { name: /Informasi/ })).toBeTruthy();
    expect(
      document.querySelector<HTMLInputElement>('input[name="site_name"]')
        ?.value,
    ).toBe('Catatan');
  });
});

describe('admin forms', () => {
  it('PostForm renders the create heading for a new post', () => {
    render(PostForm, {
      post: null,
      tags: idOptions,
      categories: idOptions,
      authors: idOptions,
    });

    expect(document.title).toContain('Tulis Artikel Baru');
    expect(document.querySelector('textarea[name="title"]')).not.toBeNull();
  });

  it('ClarificationForm renders the create heading and lets sources be added and removed', async () => {
    render(ClarificationForm, { clarification: null, verdicts: options });

    expect(document.title).toContain('Tulis Klarifikasi Baru');
    expect(document.querySelector('textarea[name="claim"]')).not.toBeNull();
    expect(
      document.querySelector('input[name="sources[0][title]"]'),
    ).toBeNull();

    await fireEvent.click(screen.getAllByRole('button', { name: /Tambah/ })[0]);

    expect(
      document.querySelector('input[name="sources[0][url]"]'),
    ).not.toBeNull();

    await fireEvent.click(
      screen.getByRole('button', { name: 'Hapus sumber 1' }),
    );

    expect(document.querySelector('input[name="sources[0][url]"]')).toBeNull();
  });

  it('ClarificationForm prefills the saved sources and updates', () => {
    render(ClarificationForm, {
      verdicts: options,
      clarification: {
        id: 1,
        title: 'Klarifikasi A',
        slug: 'klarifikasi-a',
        claim: 'Klaim',
        claim_origin: null,
        verdict: 'false',
        summary: null,
        content: '<p>Isi</p>',
        apology: null,
        cover_image: null,
        sources: [{ title: 'Sumber 1', url: 'https://example.com' }],
        updates: [{ date: '2026-10-11', note: 'Koreksi' }],
        is_published: true,
        published_at: '2026-10-11 10:00:00',
      },
    });

    expect(document.title).toContain('Ubah Klarifikasi');
    expect(
      (
        document.querySelector(
          'input[name="sources[0][url]"]',
        ) as HTMLInputElement
      ).value,
    ).toBe('https://example.com');
    expect(
      (
        document.querySelector(
          'textarea[name="updates[0][note]"]',
        ) as HTMLTextAreaElement
      ).value,
    ).toBe('Koreksi');
  });

  it('PortfolioForm renders the create heading for a new portfolio', () => {
    render(PortfolioForm, {
      portfolio: null,
      technologies: idOptions,
      services: idOptions,
      careers: [],
    });

    expect(document.querySelector('textarea[name="name"]')).not.toBeNull();
  });
});

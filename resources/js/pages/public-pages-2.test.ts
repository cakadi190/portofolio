import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';
import ContactPage from '@/pages/contact/index.svelte';
import EducationPage from '@/pages/education/index.svelte';
import PortfolioShow from '@/pages/portfolio/show.svelte';
import AboutMe from '@/pages/tentang/saya.svelte';
import CoffeePage from '@/pages/sumber-daya/tempat-ngopi.svelte';

const router = vi.hoisted(() => ({ get: vi.fn() }));

vi.mock('@inertiajs/svelte', async (importOriginal) => {
  const original = await importOriginal<typeof import('@inertiajs/svelte')>();

  return {
    ...original,
    router: Object.assign(Object.create(original.router), router),
  };
});

describe('ContactPage', () => {
  it('renders information, socials and the message form', () => {
    render(ContactPage, {
      information: [
        { label: 'Email', value: 'a@b.c', url: 'mailto:a@b.c', note: 'Bisnis' },
        { label: 'Alamat', value: 'Ngawi', url: null, note: null },
      ],
      socials: [
        {
          label: 'GitHub',
          value: '@cakadi190',
          url: 'https://github.com/cakadi190',
          note: null,
        },
      ],
      reasons: [{ value: 'kerja', label: 'Kerja sama' }],
    });

    expect(document.title).toContain('Hubungi Saya');
    expect(screen.getByRole('link', { name: 'a@b.c' })).toBeTruthy();
    expect(screen.getByText('Ngawi')).toBeTruthy();
    expect(screen.getByText(/@cakadi190$/)).toBeTruthy();
    expect(document.querySelector('#form-contact form')).not.toBeNull();
  });

  it('shows empty states', () => {
    render(ContactPage, { information: [], socials: [], reasons: [] });

    expect(screen.getByText('Belum ada informasi kontak.')).toBeTruthy();
    expect(screen.getByText('Belum ada sosial media.')).toBeTruthy();
  });
});

describe('EducationPage', () => {
  const education = {
    name: 'SMK Negeri 1',
    logo: null,
    website: 'https://www.smk.test',
    level: 'SMK',
    grade: null,
    department: 'RPL',
    studyProgram: null,
    startDate: '2018-07-01',
    endDate: '2021-06-01',
    place: 'Ngawi',
    academicScore: { label: 'Nilai', value: 3.5, scale: 4 },
  };

  it('lists educations', () => {
    render(EducationPage, { educations: [education], organizations: [] });

    expect(document.title).toContain('Pendidikan');
    expect(screen.getByText('SMK Negeri 1')).toBeTruthy();
  });

  it('switches to organizations', async () => {
    render(EducationPage, {
      educations: [education],
      organizations: [
        {
          name: 'Karang Taruna',
          description: 'Anggota',
          startDate: '2019-01-01',
          endDate: null,
        },
      ],
    });

    await fireEvent.click(screen.getByRole('button', { name: /Organisasi/ }));

    expect(screen.getByText('Karang Taruna')).toBeTruthy();
  });
});

describe('PortfolioShow', () => {
  const portfolio = {
    name: 'Proyek Hebat',
    shortDesc: 'Singkat',
    description: '<p>Deskripsi <b>lengkap</b></p>',
    image: '/p.webp',
    demoLink: 'https://demo.test',
    sourceCode: null,
    isPrivate: true,
    technologies: ['Laravel', 'Svelte'],
    slug: 'proyek-hebat',
    galleries: [{ url: '/g1.webp', title: 'Satu' }],
  };

  it('renders the project and its review summary', () => {
    render(PortfolioShow, {
      portfolio,
      reviews: [
        {
          id: 1,
          name: 'Budi',
          company: 'PT X',
          title: 'Bagus',
          rating: 5,
          comment: '<p>Mantap</p>',
          createdAt: '2024-01-01',
        },
      ],
      reviewSummary: { average: 5, count: 1 },
    });

    expect(document.title).toContain('Proyek Hebat');
    expect(screen.getByText('Budi')).toBeTruthy();
    expect(screen.getByText('Bagus')).toBeTruthy();
  });

  it('renders without reviews', () => {
    render(PortfolioShow, {
      portfolio: { ...portfolio, galleries: [], technologies: [] },
      reviews: [],
      reviewSummary: { average: 0, count: 0 },
    });

    expect(screen.getByText('Proyek Hebat')).toBeTruthy();
  });
});

describe('AboutMe', () => {
  it('renders stats and certifications', () => {
    render(AboutMe, {
      yearsExperience: 6,
      yearsServing: 3,
      totalProjects: 40,
      certifications: [
        {
          id: 1,
          title: 'Sertifikat Laravel',
          issuer: 'Lembaga',
          issuedAt: '2023-02-01',
          expiresAt: null,
          credentialId: 'ABC',
          credentialUrl: 'https://cred.test',
          file: null,
          isPdf: false,
        },
      ],
    });

    expect(document.title).toContain('');
    expect(screen.getByText('Sertifikat Laravel')).toBeTruthy();
    expect(screen.getByText(/40/)).toBeTruthy();
  });
});

describe('CoffeePage', () => {
  const place = {
    id: 1,
    name: 'Kopi Senja',
    region: 'Ngawi',
    description: 'Enak',
    image: null,
    address: 'Jl. A',
    mapUrl: null,
    opensAt: '08:00',
    closesAt: '22:00',
    parkFee: 2000,
    isRecommended: true,
    latitude: null,
    longitude: null,
    wifiProvider: null,
    wifiSpeed: null,
    wifiSpeedColor: null,
    priceTier: null,
    priceTierColor: null,
    facilities: ['Wifi'],
    galleries: [],
  };
  const places = (data: unknown[]) => ({
    data,
    current_page: 1,
    last_page: 1,
    per_page: 9,
    total: data.length,
    from: 1,
    to: data.length,
    prev_page_url: null,
    next_page_url: null,
  });

  it('shows an empty state', () => {
    render(CoffeePage, {
      places: places([]),
      regions: [],
      filters: { region: null, search: null },
    });

    expect(screen.getByText(/Belum|Tidak/)).toBeTruthy();
  });

  it('lists places', () => {
    render(CoffeePage, {
      places: places([place]),
      regions: ['Ngawi'],
      filters: { region: null, search: null },
    });

    expect(screen.getAllByText('Kopi Senja').length).toBeGreaterThan(0);
  });
});

import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it } from 'vitest';
import AppIndex from '@/pages/app/index.svelte';
import AwardPage from '@/pages/award/index.svelte';
import SpeakingPage from '@/pages/speaking/index.svelte';
import BlogIndex from '@/pages/blog/index.svelte';
import BlogShow from '@/pages/blog/show.svelte';
import CareerPage from '@/pages/career/index.svelte';
import Dashboard from '@/pages/dashboard/index.svelte';
import PortfolioIndex from '@/pages/portfolio/index.svelte';
import ServicePage from '@/pages/service/index.svelte';
import ServiceShow from '@/pages/service/show.svelte';
import AboutSite from '@/pages/tentang/situs.svelte';
import SkillPage from '@/pages/tentang/skill.svelte';
import { hrefOf } from '@/tests/helpers';

const paginated = (data: any[], overrides = {}) => ({
  data,
  current_page: 1,
  last_page: 1,
  per_page: 9,
  total: data.length,
  from: 1,
  to: data.length,
  prev_page_url: null,
  next_page_url: null,
  ...overrides,
});

describe('Dashboard', () => {
  it('renders the placeholder and sets the document title', () => {
    render(Dashboard, {
      summary: {
        blog: {
          posts_total: 0,
          posts_published: 0,
          posts_draft: 0,
          tags_total: 0,
          comments_pending: 0,
          comments_total: 0,
          recent_posts: [],
        },
        site: null,
        system: null,
      },
    });

    expect(screen.getByRole('heading', { name: 'Dasbor' })).toBeTruthy();
    expect(document.title).toContain('Dasbor');
  });
});

describe('ServicePage', () => {
  it('shows an empty state without services', () => {
    render(ServicePage, { services: [] });

    expect(document.title).toContain('Layanan Saya');
    expect(screen.getByText(/Belum Ada Layanan/)).toBeTruthy();
  });

  it('links each service to its detail page', () => {
    render(ServicePage, {
      services: [
        {
          name: 'Website',
          slug: 'website',
          color: '#0ea5e9',
          image: null,
          excerpt: 'Bikin web',
          portfoliosCount: 2,
        },
      ],
    });

    expect(hrefOf(screen.getByRole('link', { name: /Website/ }))).toBe('/layanan/website');
  });
});

describe('ServiceShow', () => {
  it('renders the description and related portfolios', () => {
    render(ServiceShow, {
      service: { name: 'Website', slug: 'website', color: null, image: null, description: '<p>Isi layanan</p>' },
      portfolios: [
        { name: 'Proyek', slug: 'proyek', image: '/p.webp', shortDesc: null, services: [], technologies: [] },
      ],
    });

    expect(screen.getByText('Isi layanan')).toBeTruthy();
    expect(hrefOf(screen.getByRole('link', { name: /Proyek/ }))).toBe('/portofolio/proyek');
  });

  it('shows an empty portfolio message', () => {
    render(ServiceShow, {
      service: { name: 'Website', slug: 'website', color: null, image: null, description: null },
      portfolios: [],
    });

    expect(screen.getByText(/Belum ada portofolio untuk layanan ini/)).toBeTruthy();
  });
});

describe('static pages', () => {
  it.each([
    ['Tentang Situs', AboutSite],
    ['Skill', SkillPage],
  ])('%s renders its header', (title, Page) => {
    render(Page as never);

    expect(
      screen.getAllByRole('heading', { level: 1 })[0].textContent,
    ).toBeTruthy();
    expect(document.title).toContain(title === 'Skill' ? '' : title);
  });
});

describe('AppIndex (home)', () => {
  it('renders the home sections', () => {
    render(AppIndex, { portfolios: [], posts: [] });

    expect(document.getElementById('homepage')).not.toBeNull();
    expect(document.title).toContain('Beranda');
    expect(screen.getByText(/Belum ada artikel/)).toBeTruthy();
    expect(screen.getByText(/Belum ada portofolio/)).toBeTruthy();
  });
});

describe('AwardPage', () => {
  const awards = [
    {
      eventName: 'Lomba A',
      title: 'Juara 1',
      icon: null,
      year: 2024,
      rank: 1,
      awardedAt: new Date().toISOString(),
    },
    {
      eventName: 'Lomba B',
      title: 'Finalis',
      icon: 'fa6-solid:medal',
      year: 2020,
      rank: null,
      awardedAt: '2020-01-01',
    },
  ];

  it('lists awards and flags only recent ones as new', () => {
    render(AwardPage, { awards });

    expect(screen.getByText('Lomba A')).toBeTruthy();
    expect(screen.getByText('2020')).toBeTruthy();
    expect(screen.getAllByText('Baru')).toHaveLength(1);
  });
});

describe('SpeakingPage', () => {
  const engagement = {
    id: 1,
    title: 'Webinar AI',
    organizer: 'Diwostech.id',
    role: 'trainer',
    roleLabel: 'Pelatih',
    format: 'online',
    formatLabel: 'Online',
    location: 'Zoom',
    startsAt: '2026-10-11 19:30',
    endsAt: '2026-10-11 21:30',
    registrationUrl: 'https://bit.ly/Diwostech8',
    description: null,
    poster: null,
    isUpcoming: true,
  };

  it('shows the schedule in WIB and a register link only for upcoming events', () => {
    render(SpeakingPage, {
      upcoming: [engagement],
      past: [{ ...engagement, id: 2, title: 'Seminar Lama', isUpcoming: false }],
    });

    expect(screen.getAllByText(/19\.30 – 21\.30 WIB/).length).toBeGreaterThan(0);
    expect(screen.getByText('Seminar Lama')).toBeTruthy();
    expect(screen.getAllByText('Daftar Sekarang')).toHaveLength(1);
  });

  it('opens the timezone modal only for upcoming events with a time', async () => {
    render(SpeakingPage, {
      upcoming: [engagement],
      past: [{ ...engagement, id: 2, title: 'Seminar Lama', isUpcoming: false }],
    });

    const triggers = screen.getAllByText('Lihat waktu di zona waktu Anda');

    expect(triggers).toHaveLength(1);

    await fireEvent.click(triggers[0]);

    expect(screen.getByText('WITA')).toBeTruthy();
    expect(screen.getByText('WIT')).toBeTruthy();
  });

  it('shows an empty archive message', () => {
    render(SpeakingPage, { upcoming: [], past: [] });

    expect(screen.getByText(/Belum ada acara yang terarsip/)).toBeTruthy();
  });
});

describe('CareerPage', () => {
  it('formats the period and marks current jobs', () => {
    render(CareerPage, {
      careers: [
        {
          position: 'Dev',
          company: 'Acme',
          location: 'Ngawi',
          startDate: '2022-03-01',
          endDate: null,
        },
        {
          position: 'Intern',
          company: 'Beta',
          location: 'Solo',
          startDate: '2020-01-01',
          endDate: '2020-06-01',
        },
      ],
    });

    expect(screen.getByText(/Maret 2022 - Sekarang/)).toBeTruthy();
    expect(screen.getByText(/Januari 2020 - Juni 2020/)).toBeTruthy();
    expect(screen.getByText('Acme | Ngawi')).toBeTruthy();
  });
});

describe('BlogIndex', () => {
  const post = (slug: string) => ({
    title: slug,
    slug,
    excerpt: null,
    coverImage: null,
    categories: [],
  });

  it('shows an empty state', () => {
    render(BlogIndex, { posts: paginated([]) });

    expect(screen.getByText('Belum Ada Artikel')).toBeTruthy();
  });

  it('lists posts with pagination', () => {
    render(BlogIndex, {
      posts: paginated([post('satu'), post('dua')], {
        last_page: 2,
        next_page_url: '/blog?page=2',
      }),
    });

    expect(hrefOf(screen.getByRole('link', { name: /satu/ }))).toBe(
      '/blog/satu',
    );
    expect(screen.getByText('Halaman 1 dari 2')).toBeTruthy();
  });
});

describe('PortfolioIndex', () => {
  it('shows an empty state', () => {
    render(PortfolioIndex, { portfolios: paginated([]) });

    expect(screen.getByText('Belum Ada Portofolio')).toBeTruthy();
  });

  it('lists portfolios', () => {
    render(PortfolioIndex, {
      portfolios: paginated([
        {
          name: 'Proyek',
          slug: 'proyek',
          image: '/p.webp',
          shortDesc: null,
          services: [],
          technologies: [],
        },
      ]),
    });

    expect(hrefOf(screen.getByRole('link', { name: /Proyek/ }))).toBe(
      '/portofolio/proyek',
    );
  });
});

describe('BlogShow', () => {
  const post = {
    slug: 'judul-artikel',
    title: 'Judul Artikel',
    excerpt: null,
    content:
      '<p>Isi <strong>artikel</strong></p><pre><code class="language-javascript">let a = 1;</code></pre>',
    coverImage: '/cover.webp',
    author: 'Cakadi',
    publishedAt: '2024-05-17T00:00:00Z',
    categories: [{ name: 'Tech', color: null }],
    tags: ['php'],
  };

  it('renders content, meta, tags and highlights code', () => {
    render(BlogShow, { post, comments: [] });

    expect(
      screen.getByRole('heading', { level: 1, name: 'Judul Artikel' }),
    ).toBeTruthy();
    expect(screen.getByText('artikel')).toBeTruthy();
    expect(screen.getByText('Tech')).toBeTruthy();
    expect(screen.getByText('php')).toBeTruthy();
    expect(screen.getAllByText(/2024/).length).toBeGreaterThan(0);
    expect(document.querySelector('code.hljs')).not.toBeNull();
    expect(hrefOf(screen.getByRole('link', { name: /Kembali/ }))).toBe('/blog');
  });

  it('handles unpublished posts without tags or cover', () => {
    render(BlogShow, {
      comments: [],
      post: {
        ...post,
        publishedAt: null,
        coverImage: null,
        tags: [],
        excerpt: 'Ringkas',
      },
    });

    expect(screen.getByText('Belum dipublikasikan')).toBeTruthy();
    expect(screen.getByText('Belum ada tag untuk artikel ini.')).toBeTruthy();
    expect(
      screen.getByRole('heading', { level: 2, name: 'Ringkas' }),
    ).toBeTruthy();
    expect(screen.queryByAltText('Judul Artikel')).toBeNull();
  });

  it('shows a nested comment thread with a login prompt for guests', () => {
    render(BlogShow, {
      post,
      comments: [
        {
          id: 1,
          body: 'Komentar utama',
          author: 'Budi',
          createdAt: '2024-05-18T00:00:00Z',
          replies: [
            {
              id: 2,
              body: 'Balasan bersarang',
              author: 'Sari',
              createdAt: '2024-05-19T00:00:00Z',
              replies: [],
            },
          ],
        },
      ],
    });

    expect(screen.getByText('Komentar (2)')).toBeTruthy();
    expect(screen.getByText('Balasan bersarang')).toBeTruthy();
    expect(screen.getByRole('link', { name: 'Masuk' })).toBeTruthy();
    expect(screen.queryByRole('button', { name: 'Balas' })).toBeNull();
    expect(screen.queryByLabelText('Tulis komentar')).toBeNull();
  });
});

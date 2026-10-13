import { render, screen } from '@testing-library/svelte';
import { describe, expect, it } from 'vitest';
import GuestbookPage from '@/pages/guestbook/index.svelte';
import NotesIndex from '@/pages/notes/index.svelte';
import NotesShow from '@/pages/notes/show.svelte';
import StatisticPage from '@/pages/statistic/index.svelte';

const paginated = (data: unknown[]) => ({
  data,
  current_page: 1,
  last_page: 1,
  prev_page_url: null,
  next_page_url: null,
});

describe('NotesIndex', () => {
  it('lists notes with views and tag filters', () => {
    render(NotesIndex, {
      notes: paginated([
        {
          title: 'Auth Context',
          slug: 'auth-context',
          excerpt: null,
          publishedAt: null,
          views: 1200,
          tags: ['react'],
        },
      ]),
      tags: ['react'],
      activeTag: null,
    });

    expect(document.title).toContain('Catatan Singkat');
    expect(screen.getByText('Auth Context')).toBeTruthy();
    expect(screen.getByText(/1\.200 tayangan/)).toBeTruthy();
    expect(screen.getByRole('link', { name: 'react' })).toBeTruthy();
  });

  it('shows an empty state', () => {
    render(NotesIndex, { notes: paginated([]), tags: [], activeTag: null });

    expect(screen.getByText('Belum Ada Catatan')).toBeTruthy();
  });
});

describe('NotesShow', () => {
  it('renders the note body, reading time and views', () => {
    render(NotesShow, {
      note: {
        slug: 'auth-context',
        title: 'Auth Context',
        excerpt: null,
        content: '<p>Isi catatan</p>',
        publishedAt: '2026-10-05T00:00:00Z',
        readingMinutes: 4,
        views: 3,
        tags: ['react'],
      },
    });

    expect(screen.getByText('Isi catatan')).toBeTruthy();
    expect(screen.getByText(/4 menit baca/)).toBeTruthy();
    expect(screen.getByText(/3 tayangan/)).toBeTruthy();
  });
});

describe('GuestbookPage', () => {
  it('renders nested entries and asks guests to sign in', () => {
    render(GuestbookPage, {
      entries: [
        {
          id: 1,
          body: 'Halo dunia',
          author: 'Budi',
          createdAt: '2026-10-05T00:00:00Z',
          replies: [
            {
              id: 2,
              body: 'Halo juga',
              author: 'Cak Adi',
              createdAt: '2026-10-06T00:00:00Z',
              replies: [],
            },
          ],
        },
      ],
    });

    expect(screen.getByText('Halo dunia')).toBeTruthy();
    expect(screen.getByText('Halo juga')).toBeTruthy();
    expect(screen.getByText('Pesan (2)')).toBeTruthy();
    expect(screen.getByRole('link', { name: 'Masuk' })).toBeTruthy();
  });
});

describe('StatisticPage', () => {
  it('shows totals and the per-page view tables', () => {
    render(StatisticPage, {
      siteViews: 1500,
      blog: {
        count: 1,
        views: 40,
        rows: [{ title: 'Artikel A', slug: 'a', views: 40 }],
      },
      notes: { count: 0, views: 0, rows: [] },
      guestbookCount: 3,
    });

    expect(screen.getByText('1.500')).toBeTruthy();
    expect(screen.getByRole('link', { name: 'Artikel A' })).toBeTruthy();
    expect(screen.getByText('Belum ada data.')).toBeTruthy();
  });
});

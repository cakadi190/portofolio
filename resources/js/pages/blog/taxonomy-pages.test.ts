import { render, screen } from '@testing-library/svelte';
import { describe, expect, it } from 'vitest';
import BlogAuthor from '@/pages/blog/author.svelte';
import BlogCategory from '@/pages/blog/category.svelte';
import BlogTag from '@/pages/blog/tag.svelte';
import { hrefOf } from '@/tests/helpers';

const paginated = (data: any[]) => ({
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

const post = (overrides = {}) => ({
  title: 'Judul Satu',
  slug: 'judul-satu',
  excerpt: 'Ringkasan',
  coverImage: null,
  publishedAt: '2024-05-17T00:00:00Z',
  author: { name: 'Penulis', username: 'penulis' },
  categories: [{ name: 'Tech', slug: 'tech', color: null }],
  tags: [{ name: 'php', slug: 'php' }],
  editUrl: null,
  ...overrides,
});

describe('blog taxonomy pages', () => {
  it('lists category posts with working links and no edit button for the public', () => {
    render(BlogCategory, {
      category: { name: 'Tech', slug: 'tech', color: null },
      posts: paginated([post()]),
      relatedCategories: [
        { name: 'Gaya Hidup', slug: 'gaya-hidup', color: null, count: 2 },
      ],
      relatedTags: [{ name: 'laravel', slug: 'laravel', count: 1 }],
    } as never);

    expect(
      screen.getByRole('heading', { level: 1, name: 'Tech' }),
    ).toBeTruthy();
    expect(hrefOf(screen.getByRole('link', { name: 'Judul Satu' }))).toBe(
      '/blog/judul-satu',
    );
    expect(hrefOf(screen.getByRole('link', { name: 'Penulis' }))).toBe(
      '/blog/@penulis',
    );
    expect(screen.queryByRole('link', { name: 'php' })).toBeNull();
    expect(screen.queryByRole('link', { name: /Edit Artikel/ })).toBeNull();
  });

  it('renders empty states and tolerates posts without optional relations', () => {
    render(BlogTag, {
      tag: { name: 'kosong', slug: 'kosong' },
      posts: paginated([]),
      relatedTags: [],
    } as never);
    expect(
      screen.getByText(/Belum ada artikel yang diterbitkan dengan tag kosong/),
    ).toBeTruthy();
  });

  it('renders the author profile and posts lacking author, tags and categories', () => {
    render(BlogAuthor, {
      author: { name: 'Penulis', username: 'penulis', avatar: null },
      posts: paginated([post({ author: null, tags: [], categories: [] })]),
    });

    expect(
      screen.getByRole('heading', { level: 1, name: 'Penulis' }),
    ).toBeTruthy();
    expect(hrefOf(screen.getByRole('link', { name: 'Judul Satu' }))).toBe(
      '/blog/judul-satu',
    );
  });

  it('shows an empty state for an author without posts', () => {
    render(BlogAuthor, {
      author: { name: 'Baru', username: 'baru', avatar: null },
      posts: paginated([]),
    });
    expect(screen.getByText('Baru belum menerbitkan artikel.')).toBeTruthy();
  });
});

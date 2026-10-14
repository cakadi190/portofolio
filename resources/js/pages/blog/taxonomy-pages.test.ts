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
  author: { name: 'Penulis', slug: 'penulis' },
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
    });

    expect(
      screen.getByRole('heading', { level: 1, name: 'Tech' }),
    ).toBeTruthy();
    expect(hrefOf(screen.getByRole('link', { name: 'Judul Satu' }))).toBe(
      '/blog/judul-satu',
    );
    expect(hrefOf(screen.getByRole('link', { name: 'Penulis' }))).toBe(
      '/blog/authors/penulis',
    );
    expect(hrefOf(screen.getByRole('link', { name: 'php' }))).toBe(
      '/blog/tags/php',
    );
    expect(hrefOf(screen.getByRole('link', { name: /Gaya Hidup/ }))).toBe(
      '/blog/categories/gaya-hidup',
    );
    expect(hrefOf(screen.getByRole('link', { name: /laravel/ }))).toBe(
      '/blog/tags/laravel',
    );
    expect(screen.queryByRole('link', { name: /Edit Artikel/ })).toBeNull();
  });

  it('shows an edit shortcut per post when the server provides one', () => {
    render(BlogTag, {
      tag: { name: 'php', slug: 'php' },
      posts: paginated([post({ editUrl: '/admin/posts/3/edit' })]),
      relatedTags: [],
    });

    expect(hrefOf(screen.getByRole('link', { name: /Edit Artikel/ }))).toBe(
      '/admin/posts/3/edit',
    );
  });

  it('renders empty states and tolerates posts without optional relations', () => {
    render(BlogTag, {
      tag: { name: 'kosong', slug: 'kosong' },
      posts: paginated([]),
      relatedTags: [],
    });
    expect(
      screen.getByText(/Belum ada artikel yang diterbitkan dengan tag kosong/),
    ).toBeTruthy();
  });

  it('renders the author profile and posts lacking author, tags and categories', () => {
    render(BlogAuthor, {
      author: { name: 'Penulis', slug: 'penulis', avatar: null },
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
      author: { name: 'Baru', slug: 'baru', avatar: null },
      posts: paginated([]),
    });
    expect(screen.getByText('Baru belum menerbitkan artikel.')).toBeTruthy();
  });
});

import { fireEvent, render, screen } from '@testing-library/svelte';
import { page } from '@inertiajs/svelte';
import { describe, expect, it } from 'vitest';
import PostForm from '@/pages/admin/posts/form.svelte';

const post = {
  id: 1,
  title: 'Judul',
  slug: 'judul',
  excerpt: 'Ringkas',
  content: '<p>Isi</p>',
  cover_image: null,
  user_id: null,
  is_published: false,
  published_at: null,
  tags: [],
  categories: [],
  translations: {
    title: { id: 'Judul', en: 'Title' },
    excerpt: { id: 'Ringkas', en: '' },
    content: { id: '<p>Isi</p>', en: '<p>Body</p>' },
  },
};

function renderForm(translations = post.translations) {
  page.props = {
    ...page.props,
    locales: [
      { code: 'id', label: 'Bahasa Indonesia', flag: 'fi-id' },
      { code: 'en', label: 'English', flag: 'fi-us' },
    ],
  };

  return render(PostForm, {
    post: { ...post, translations },
    tags: [],
    categories: [],
    authors: [],
  } as never);
}

const field = (selector: string) =>
  document.querySelector<HTMLInputElement>(selector);

describe('PostForm language tabs', () => {
  it('edits Indonesian first and still submits English from hidden inputs', () => {
    renderForm();

    expect(document.querySelector('textarea[name="title"]')).not.toBeNull();
    expect(field('input[type="hidden"][name="title_en"]')?.value).toBe('Title');
    expect(field('input[type="hidden"][name="content_en"]')?.value).toBe(
      '<p>Body</p>',
    );
  });

  it('shows one tab per content language above the title', () => {
    renderForm();

    expect(screen.getByRole('tablist', { name: 'Bahasa konten' })).toBeTruthy();
    expect(
      screen
        .getByRole('tab', { name: /Konten Bahasa Indonesia/ })
        .getAttribute('aria-selected'),
    ).toBe('true');
    expect(
      screen
        .getByRole('tab', { name: /Konten Bahasa Inggris/ })
        .getAttribute('aria-selected'),
    ).toBe('false');
    expect(screen.queryByRole('button', { name: /Bahasa konten:/ })).toBeNull();
  });

  it('switches the fields to English and keeps Indonesian submitted', async () => {
    renderForm();

    await fireEvent.click(
      screen.getByRole('tab', { name: /Konten Bahasa Inggris/ }),
    );

    const title = document.querySelector<HTMLTextAreaElement>(
      'textarea[name="title_en"]',
    );

    expect(title?.value).toBe('Title');
    expect(title?.required).toBe(false);
    expect(field('input[type="hidden"][name="title"]')?.value).toBe('Judul');
    expect(
      document.querySelector('textarea[name="excerpt_en"]'),
    ).not.toBeNull();
    expect(
      screen
        .getByRole('tab', { name: /Konten Bahasa Inggris/ })
        .getAttribute('aria-selected'),
    ).toBe('true');
  });

  it('flags a language tab that has no text yet', () => {
    renderForm({
      title: { id: 'Judul', en: '' },
      excerpt: { id: '', en: '' },
      content: { id: '<p>Isi</p>', en: '' },
    });

    expect(
      screen.getByRole('img', { name: 'Belum diterjemahkan' }),
    ).toBeTruthy();
  });
});

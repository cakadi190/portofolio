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

describe('PostForm language dropdown', () => {
  it('edits Indonesian first and still submits English from hidden inputs', () => {
    renderForm();

    expect(document.querySelector('textarea[name="title"]')).not.toBeNull();
    expect(field('input[type="hidden"][name="title_en"]')?.value).toBe('Title');
    expect(field('input[type="hidden"][name="content_en"]')?.value).toBe(
      '<p>Body</p>',
    );
  });

  it('offers both content languages next to the preview button', async () => {
    renderForm();

    await fireEvent.click(
      screen.getByRole('button', {
        name: /Bahasa konten: Konten Bahasa Indonesia/,
      }),
    );

    expect(screen.getByText('Konten Bahasa Indonesia')).toBeTruthy();
    expect(screen.getByText('Konten Bahasa Inggris')).toBeTruthy();
  });

  it('switches the fields to English and keeps Indonesian submitted', async () => {
    renderForm();

    await fireEvent.click(
      screen.getByRole('button', { name: /Bahasa konten/ }),
    );
    await fireEvent.click(screen.getByText('Konten Bahasa Inggris'));

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
      screen.getByRole('button', {
        name: /Bahasa konten: Konten Bahasa Inggris/,
      }),
    ).toBeTruthy();
  });

  it('flags an English version that has no text yet', async () => {
    renderForm({
      title: { id: 'Judul', en: '' },
      excerpt: { id: '', en: '' },
      content: { id: '<p>Isi</p>', en: '' },
    });

    await fireEvent.click(
      screen.getByRole('button', { name: /Bahasa konten/ }),
    );
    await fireEvent.click(screen.getByText('Konten Bahasa Inggris'));

    expect(
      screen.getByRole('img', { name: 'Belum diterjemahkan' }),
    ).toBeTruthy();
  });
});

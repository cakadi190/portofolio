import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it } from 'vitest';
import PostMeta from '@/components/blog/post-meta.svelte';

const props = {
  author: { name: 'Cak Adi', username: 'cak-adi', avatar: null },
  publishedAt: '2026-10-14T15:32:00Z',
  title: 'Judul Artikel',
};

describe('PostMeta', () => {
  it('shows the author and a share button', () => {
    render(PostMeta, props);

    expect(screen.getByText('Cak Adi')).toBeTruthy();
    expect(screen.getByRole('button', { name: /Bagikan/ })).toBeTruthy();
  });

  it('builds share targets for the current page when sharing is opened', async () => {
    render(PostMeta, props);

    await fireEvent.click(screen.getByRole('button', { name: /Bagikan/ }));

    const whatsapp = document.querySelector<HTMLAnchorElement>(
      'a[aria-label="Bagikan lewat WhatsApp"]',
    );

    expect(whatsapp?.href).toContain('wa.me');
    expect(whatsapp?.href).toContain(encodeURIComponent('Judul Artikel'));
    expect(
      document.querySelector('a[aria-label="Bagikan lewat Email"]')?.getAttribute('href'),
    ).toMatch(/^mailto:/);
    expect(document.querySelector('button[aria-label="Salin tautan"]')).toBeTruthy();
  });
});

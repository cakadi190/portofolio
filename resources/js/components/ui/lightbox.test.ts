import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';
import Lightbox from '@/components/ui/lightbox.svelte';

vi.mock('@/components/ui/pdf-canvas-viewer.svelte', async () => await import('@/tests/stubs/pdf-viewer-stub.svelte'));

const images = [
  { url: '/a.webp', title: 'Satu' },
  { url: '/b.webp', title: 'Dua' },
  { url: '/c.webp', title: 'Tiga' },
];

describe('Lightbox', () => {
  it('renders nothing while closed', () => {
    render(Lightbox, { open: false, images });

    expect(screen.queryByRole('dialog')).toBeNull();
  });

  it('shows the current image in a dialog and locks body scroll', () => {
    render(Lightbox, { open: true, images, index: 1 });

    expect(screen.getByRole('dialog', { name: 'Dua' })).toBeTruthy();
    expect(screen.getByText('2 / 3')).toBeTruthy();
    expect(document.body.style.overflow).toBe('hidden');
  });

  it('builds a single image from src/alt', () => {
    render(Lightbox, { open: true, src: '/x.webp', alt: 'Foto' });

    expect(screen.getByRole('dialog', { name: 'Foto' })).toBeTruthy();
    expect(screen.queryByLabelText('Sebelumnya')).toBeNull();
  });

  it('pages with buttons and disables edges', async () => {
    render(Lightbox, { open: true, images, index: 0 });
    const previous = () =>
      screen.getAllByLabelText('Sebelumnya').at(-1) as HTMLButtonElement;
    const next = () =>
      screen.getAllByLabelText('Berikutnya').at(-1) as HTMLButtonElement;

    expect(previous().disabled).toBe(true);

    await fireEvent.click(next());
    expect(screen.getAllByText('2 / 3').length).toBeGreaterThan(0);

    await fireEvent.click(next());
    expect(next().disabled).toBe(true);
  });

  it('pages with arrow keys', async () => {
    render(Lightbox, { open: true, images, index: 0 });

    await fireEvent.keyDown(window, { key: 'ArrowRight' });
    expect(screen.getByText('2 / 3')).toBeTruthy();

    await fireEvent.keyDown(window, { key: 'ArrowLeft' });
    expect(screen.getByText('1 / 3')).toBeTruthy();
  });

  it('zooms in and out and resets', async () => {
    render(Lightbox, { open: true, images });
    const stage = document.querySelector('.lightbox-stage, .lightbox-image-stage') ?? document.body;
    stage.getBoundingClientRect = () => ({ left: 0, top: 0, width: 800, height: 600, right: 800, bottom: 600 }) as DOMRect;

    expect(screen.getByText('100%')).toBeTruthy();
    expect((screen.getByLabelText('Reset zoom') as HTMLButtonElement).disabled).toBe(true);

    await fireEvent.click(screen.getByLabelText('Perbesar'));
    expect(screen.getByText('150%')).toBeTruthy();

    await fireEvent.keyDown(window, { key: '+' });
    expect(screen.getByText('200%')).toBeTruthy();

    await fireEvent.keyDown(window, { key: '-' });
    expect(screen.getByText('150%')).toBeTruthy();

    await fireEvent.keyDown(window, { key: '0' });
    expect(screen.getByText('100%')).toBeTruthy();
  });

  it('closes with the close button and Escape', async () => {
    const { unmount } = render(Lightbox, { open: true, images });
    await fireEvent.click(screen.getByLabelText('Tutup pratinjau'));
    await vi.waitFor(() => expect(screen.queryByRole('dialog')).toBeNull());
    unmount();

    render(Lightbox, { open: true, images });
    await fireEvent.keyDown(window, { key: 'Escape' });
    await vi.waitFor(() => expect(screen.queryByRole('dialog')).toBeNull());
  });

  it('restores body scrolling when it closes', async () => {
    render(Lightbox, { open: true, images });
    await fireEvent.keyDown(window, { key: 'Escape' });

    await vi.waitFor(() => expect(document.body.style.overflow).toBe(''));
  });

  it('offers a download link for images', () => {
    render(Lightbox, { open: true, images });

    expect(screen.getByLabelText('Unduh gambar').getAttribute('href')).toBe('/a.webp');
  });

  it('renders a native video with a download link', () => {
    render(Lightbox, { open: true, images: [{ url: '/clip.mp4', title: 'Klip' }] });

    expect(document.querySelector('video')).not.toBeNull();
    expect(screen.getByLabelText('Unduh video')).toBeTruthy();
    expect(screen.queryByLabelText('Perbesar')).toBeNull();
  });

  it('embeds known video providers and links out', () => {
    render(Lightbox, { open: true, images: [{ url: 'https://youtu.be/abc123', title: 'Yt' }] });

    expect(document.querySelector('iframe')?.getAttribute('src')).toBe(
      'https://www.youtube-nocookie.com/embed/abc123',
    );
    expect(screen.getByLabelText('Buka di tab baru').getAttribute('target')).toBe('_blank');
  });

  it('delegates PDFs to the PDF viewer and ignores zoom keys', async () => {
    render(Lightbox, { open: true, images: [{ url: '/doc.pdf', title: 'Dokumen' }] });

    expect(screen.getByTestId('pdf-stub').textContent).toContain('/doc.pdf');
    expect(screen.queryByLabelText('Perbesar')).toBeNull();

    await fireEvent.click(screen.getByText('tutup-pdf'));
    await vi.waitFor(() => expect(screen.queryByRole('dialog')).toBeNull());
  });

  it('respects an explicit media type', () => {
    render(Lightbox, { open: true, images: [{ url: '/file', title: 'X', type: 'pdf' }] });

    expect(screen.getByTestId('pdf-stub')).toBeTruthy();
  });
});

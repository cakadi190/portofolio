import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';
import MediaField from '@/components/media/media-field.svelte';
import MediaGalleryField from '@/components/media/media-gallery-field.svelte';

vi.mock('@/components/media/media-picker-modal.svelte', async () => ({
  default: (await import('@/tests/stubs/media-picker-stub.svelte')).default,
}));
vi.mock('@/components/ui/lightbox.svelte', async () => ({
  default: (await import('@/tests/stubs/lightbox-stub.svelte')).default,
}));

const hidden = (name: string) =>
  document.querySelector<HTMLInputElement>(`input[name="${CSS.escape(name)}"]`);

describe('MediaField', () => {
  it('shows an empty state with a pick button', () => {
    render(MediaField, { name: 'cover' });

    expect(screen.getByText('Belum ada media dipilih')).toBeTruthy();
    expect(screen.getByRole('button', { name: 'Pilih Media' })).toBeTruthy();
    expect(hidden('cover')?.value).toBe('');
  });

  it('previews the stored image path', () => {
    render(MediaField, { name: 'cover', value: 'media/a.webp' });

    expect(screen.getByAltText('Pratinjau').getAttribute('src')).toBe(
      '/storage/media/a.webp',
    );
    expect(hidden('cover')?.value).toBe('media/a.webp');
    expect(screen.getByRole('button', { name: 'Ganti Media' })).toBeTruthy();
  });

  it('shows the file name for PDFs', () => {
    render(MediaField, { name: 'cv', value: 'media/cv.pdf', accept: 'all' });

    expect(screen.getByText('cv.pdf')).toBeTruthy();
    expect(screen.queryByAltText('Pratinjau')).toBeNull();
  });

  it('removes the media unless required', async () => {
    render(MediaField, { name: 'cover', value: 'media/a.webp' });

    await fireEvent.click(screen.getByLabelText('Lepas media'));

    expect(hidden('cover')?.value).toBe('');
  });

  it('hides the remove button when required', () => {
    render(MediaField, {
      name: 'cover',
      value: 'media/a.webp',
      required: true,
    });

    expect(screen.queryByLabelText('Lepas media')).toBeNull();
  });

  it('adopts the path chosen in the picker', async () => {
    render(MediaField, { name: 'cover' });

    await fireEvent.click(screen.getByRole('button', { name: 'Pilih Media' }));
    await fireEvent.click(screen.getByText('pick-new'));

    expect(hidden('cover')?.value).toBe('media/new.webp');
  });

  it('marks invalid state', () => {
    const { container } = render(MediaField, { name: 'x', invalid: true });

    expect(container.querySelector('.media-field.is-invalid')).not.toBeNull();
  });
});

describe('MediaGalleryField', () => {
  const value = [
    { image_url: 'media/a.webp', description: 'A' },
    { image_url: 'media/b.webp', description: null },
  ];

  it('shows an empty message', () => {
    render(MediaGalleryField);

    expect(screen.getByText('Belum ada gambar galeri.')).toBeTruthy();
  });

  it('submits indexed fields for each image', () => {
    render(MediaGalleryField, { value });

    expect(hidden('galleries[0][image_url]')?.value).toBe('media/a.webp');
    expect(hidden('galleries[0][description]')?.value).toBe('A');
    expect(hidden('galleries[1][image_url]')?.value).toBe('media/b.webp');
  });

  it('adds picked images once and ignores duplicates', async () => {
    render(MediaGalleryField, { value });

    await fireEvent.click(
      screen.getByRole('button', { name: /Tambah Gambar/ }),
    );
    await fireEvent.click(screen.getByText('pick-new'));
    await fireEvent.click(screen.getByText('pick-new'));

    expect(hidden('galleries[2][image_url]')?.value).toBe('media/new.webp');
    expect(hidden('galleries[3][image_url]')).toBeNull();
  });

  it('removes an image', async () => {
    render(MediaGalleryField, { value });

    await fireEvent.click(screen.getAllByLabelText('Hapus dari galeri')[0]);

    expect(hidden('galleries[0][image_url]')?.value).toBe('media/b.webp');
    expect(hidden('galleries[1][image_url]')).toBeNull();
  });

  it('shows validation errors', () => {
    render(MediaGalleryField, {
      value,
      errors: { 'galleries.0.image_url': 'Wajib diisi' },
    });

    expect(screen.getByText('Wajib diisi')).toBeTruthy();
  });

  it('opens the lightbox for a preview', async () => {
    render(MediaGalleryField, { value });

    await fireEvent.click(screen.getAllByLabelText('Pratinjau gambar')[1]);

    expect(screen.getByTestId('lightbox-stub').textContent).toContain('1');
  });
});

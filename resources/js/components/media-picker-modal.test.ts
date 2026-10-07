import { fireEvent, render, screen } from '@testing-library/svelte';
import { afterEach, describe, expect, it, vi } from 'vitest';
import MediaPickerModal from '@/components/media/media-picker-modal.svelte';

const uploadMedia = vi.hoisted(() => vi.fn());
const toast = vi.hoisted(() => ({ error: vi.fn() }));

vi.mock('@/lib/media', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@/lib/media')>()),
  uploadMedia,
}));
vi.mock('@/lib/toast', () => ({ toast }));

const media = (id: number, extra = {}) => ({
  id,
  path: `media/${id}.webp`,
  name: `file-${id}`,
  alt: null,
  mime_type: 'image/webp',
  size: 2048,
  is_image: true,
  url: `/storage/media/${id}.webp`,
  ...extra,
});

function mockLibrary(items = [media(1), media(2)], lastPage = 1) {
  const fetchMock = vi.fn().mockResolvedValue({
    ok: true,
    json: async () => ({ data: items, last_page: lastPage }),
  });
  vi.stubGlobal('fetch', fetchMock);

  return fetchMock;
}

afterEach(() => {
  vi.useRealTimers();
});

describe('MediaPickerModal', () => {
  it('does not load until opened', () => {
    const fetchMock = mockLibrary();

    render(MediaPickerModal, { onSelect: vi.fn() });

    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('loads the library and lists items', async () => {
    const fetchMock = mockLibrary();
    render(MediaPickerModal, {
      open: true,
      accept: 'image',
      onSelect: vi.fn(),
    });

    expect(await screen.findByText('file-1')).toBeTruthy();
    expect(screen.getAllByText('2 KB')).toHaveLength(2);
    expect(String(fetchMock.mock.calls[0][0])).toContain('type=image');
  });

  it('selects an item and closes in single mode', async () => {
    mockLibrary();
    const onSelect = vi.fn();
    render(MediaPickerModal, { open: true, onSelect });

    await fireEvent.click(await screen.findByTitle('file-2'));

    expect(onSelect).toHaveBeenCalledWith(expect.objectContaining({ id: 2 }));
  });

  it('collects several items in multiple mode', async () => {
    mockLibrary();
    const onSelect = vi.fn();
    render(MediaPickerModal, { open: true, multiple: true, onSelect });

    await fireEvent.click(await screen.findByTitle('file-1'));
    await fireEvent.click(screen.getByTitle('file-2'));
    await fireEvent.click(screen.getByTitle('file-2'));
    await fireEvent.click(
      screen.getByRole('button', { name: 'Tambahkan 1 berkas', hidden: true }),
    );

    expect(onSelect).toHaveBeenCalledTimes(1);
    expect(onSelect).toHaveBeenCalledWith(
      expect.objectContaining({ id: 1 }),
      0,
      expect.anything(),
    );
  });

  it('shows the empty state', async () => {
    mockLibrary([]);
    render(MediaPickerModal, { open: true, onSelect: vi.fn() });

    expect(await screen.findByText(/Belum ada media/)).toBeTruthy();
  });

  it('reports load failures', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false }));
    render(MediaPickerModal, { open: true, onSelect: vi.fn() });

    expect(await screen.findByText('Pustaka media gagal dimuat.')).toBeTruthy();
    expect(toast.error).toHaveBeenCalled();
  });

  it('pages through the library', async () => {
    const fetchMock = mockLibrary([media(1)], 3);
    render(MediaPickerModal, { open: true, onSelect: vi.fn() });

    await screen.findByText('Halaman 1 dari 3');
    await fireEvent.click(
      screen.getByRole('button', { name: 'Berikutnya', hidden: true }),
    );

    await vi.waitFor(() =>
      expect(String(fetchMock.mock.calls.at(-1)?.[0])).toContain('page=2'),
    );
  });

  it('debounces search', async () => {
    const fetchMock = mockLibrary();
    render(MediaPickerModal, { open: true, onSelect: vi.fn() });
    await screen.findByText('file-1');

    vi.useFakeTimers();
    await fireEvent.input(screen.getByLabelText('Cari media'), {
      target: { value: 'logo' },
    });
    vi.advanceTimersByTime(350);

    expect(String(fetchMock.mock.calls.at(-1)?.[0])).toContain('search=logo');
  });

  it('uploads files then reloads', async () => {
    const fetchMock = mockLibrary();
    uploadMedia.mockResolvedValue(media(9));
    const { container } = render(MediaPickerModal, {
      open: true,
      onSelect: vi.fn(),
    });
    await screen.findByText('file-1');

    const input =
      container.ownerDocument.querySelector<HTMLInputElement>(
        'input[type="file"]',
      )!;
    const file = new File(['x'], 'a.png', { type: 'image/png' });
    await fireEvent.change(input, { target: { files: [file] } });

    await vi.waitFor(() => expect(uploadMedia).toHaveBeenCalledWith(file));
    await vi.waitFor(() =>
      expect(fetchMock.mock.calls.length).toBeGreaterThan(1),
    );
  });

  it('rejects non-images when only images are accepted', async () => {
    mockLibrary();
    render(MediaPickerModal, {
      open: true,
      accept: 'image',
      onSelect: vi.fn(),
    });
    await screen.findByText('file-1');

    const input =
      document.querySelector<HTMLInputElement>('input[type="file"]')!;
    const pdf = new File(['x'], 'a.pdf', { type: 'application/pdf' });
    await fireEvent.change(input, { target: { files: [pdf] } });

    expect(
      await screen.findByText(
        'Hanya berkas gambar yang diperbolehkan di sini.',
      ),
    ).toBeTruthy();
    expect(uploadMedia).not.toHaveBeenCalled();
  });
});

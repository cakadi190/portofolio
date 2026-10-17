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
    json: async () => ({
      data: items,
      current_page: 1,
      last_page: lastPage,
      total: items.length,
      from: 1,
      to: items.length,
    }),
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

  const confirm = (label: string) =>
    screen.getByRole('button', { name: label, hidden: true });

  it('Choose Single keeps one item, replaces it and confirms a single media', async () => {
    mockLibrary();
    const onSelect = vi.fn();
    render(MediaPickerModal, { open: true, onSelect });

    const first = (await screen.findByLabelText(
      'Pilih file-1',
    )) as HTMLInputElement;
    const second = screen.getByLabelText('Pilih file-2') as HTMLInputElement;
    await fireEvent.click(first);
    await fireEvent.click(second);

    expect(first.checked).toBe(false);
    expect(second.checked).toBe(true);
    expect(onSelect).not.toHaveBeenCalled();

    await fireEvent.click(confirm('Pilih 1 berkas'));

    expect(onSelect).toHaveBeenCalledTimes(1);
    expect(onSelect).toHaveBeenCalledWith(expect.objectContaining({ id: 2 }));
  });

  it('disables confirm until something is chosen and cancel never selects', async () => {
    mockLibrary();
    const onSelect = vi.fn();
    render(MediaPickerModal, { open: true, onSelect });
    await screen.findByText('file-1');

    expect((confirm('Pilih 0 berkas') as HTMLButtonElement).disabled).toBe(
      true,
    );

    await fireEvent.click(screen.getByLabelText('Pilih file-1'));
    await fireEvent.click(
      screen.getByRole('button', { name: 'Batal', hidden: true }),
    );

    expect(onSelect).not.toHaveBeenCalled();
  });

  it('Choose Multi returns the picked items in selection order', async () => {
    mockLibrary([media(1), media(2), media(3)]);
    const onSelectMultiple = vi.fn();
    render(MediaPickerModal, {
      open: true,
      mode: 'multiple',
      onSelectMultiple,
    });

    await fireEvent.click(await screen.findByLabelText('Pilih file-3'));
    await fireEvent.click(screen.getByLabelText('Pilih file-1'));
    await fireEvent.click(screen.getByLabelText('Pilih file-2'));
    await fireEvent.click(screen.getByLabelText('Pilih file-2'));

    expect(screen.getByText('2 berkas dipilih')).toBeTruthy();
    await fireEvent.click(confirm('Pilih 2 berkas'));

    expect(onSelectMultiple).toHaveBeenCalledWith([
      expect.objectContaining({ id: 3 }),
      expect.objectContaining({ id: 1 }),
    ]);
  });

  it('starts from the initial selection', async () => {
    mockLibrary();
    render(MediaPickerModal, {
      open: true,
      mode: 'multiple',
      initialSelection: [media(1)] as never,
      onSelectMultiple: vi.fn(),
    });

    expect(
      ((await screen.findByLabelText('Pilih file-1')) as HTMLInputElement)
        .checked,
    ).toBe(true);
    expect(screen.getByText('1 berkas dipilih')).toBeTruthy();
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

  it('paginates server-side and keeps the selection across pages', async () => {
    const fetchMock = vi.fn().mockImplementation(async (url: string) => {
      const page = String(url).includes('page=2') ? 2 : 1;

      return {
        ok: true,
        json: async () => ({
          data: [media(page)],
          current_page: page,
          last_page: 2,
          total: 2,
          from: page,
          to: page,
        }),
      };
    });
    vi.stubGlobal('fetch', fetchMock);
    const onSelectMultiple = vi.fn();
    render(MediaPickerModal, {
      open: true,
      mode: 'multiple',
      onSelectMultiple,
    });

    await fireEvent.click(await screen.findByLabelText('Pilih file-1'));
    await fireEvent.click(
      screen.getByRole('button', { name: 'Halaman berikutnya', hidden: true }),
    );

    await screen.findByText('file-2');
    expect(String(fetchMock.mock.calls.at(-1)?.[0])).toContain('page=2');
    await fireEvent.click(screen.getByLabelText('Pilih file-2'));
    await fireEvent.click(
      screen.getByRole('button', { name: 'Halaman sebelumnya', hidden: true }),
    );
    await screen.findByText('file-1');

    expect(
      (screen.getByLabelText('Pilih file-1') as HTMLInputElement).checked,
    ).toBe(true);
    await fireEvent.click(confirm('Pilih 2 berkas'));

    expect(onSelectMultiple).toHaveBeenCalledWith([
      expect.objectContaining({ id: 1 }),
      expect.objectContaining({ id: 2 }),
    ]);
  });

  it('renders srcset and sizes for images that have variants', async () => {
    mockLibrary([
      media(1, {
        srcset: '/storage/a-320w.webp 320w, /storage/a.webp 900w',
        width: 900,
        height: 600,
      }),
    ]);
    render(MediaPickerModal, { open: true, onSelect: vi.fn() });

    const image = (await screen.findByAltText('file-1')) as HTMLImageElement;

    expect(image.getAttribute('srcset')).toContain('320w');
    expect(image.getAttribute('sizes')).toBeTruthy();
    expect(image.getAttribute('width')).toBe('900');
    expect(image.getAttribute('src')).toBe('/storage/media/1.webp');
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

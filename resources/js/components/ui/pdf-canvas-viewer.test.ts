import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { fireEvent, render, screen } from '@testing-library/svelte';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import PdfCanvasViewer from '@/components/ui/pdf-canvas-viewer.svelte';

const pdf = vi.hoisted(() => ({
  getDocument: vi.fn(),
  destroy: vi.fn(),
  getPage: vi.fn(),
  pages: [] as string[][],
}));

vi.mock('pdfjs-dist', () => ({
  GlobalWorkerOptions: {},
  getDocument: pdf.getDocument,
}));
vi.mock('pdfjs-dist/build/pdf.worker.min.mjs?url', () => ({ default: '/worker.js' }));

function fakePage(texts: string[]) {
  return {
    rotate: 0,
    view: [0, 0, 600, 800],
    getViewport: ({ scale = 1 }: { scale?: number }) => ({
      width: 600 * scale,
      height: 800 * scale,
      transform: [scale, 0, 0, -scale, 0, 800 * scale],
    }),
    render: () => ({ promise: Promise.resolve(), cancel: vi.fn() }),
    getTextContent: async () => ({
      items: texts.map((str, index) => ({
        str,
        transform: [1, 0, 0, 1, 10, 700 - index * 20],
        width: 200,
        height: 12,
      })),
    }),
    getAnnotations: async () => [
      { rect: [0, 0, 10, 10], contents: 'Catatan penting', title: 'Penulis' },
    ],
  };
}

function loadDocument(pages: string[][]) {
  pdf.getDocument.mockImplementation(() => ({
    promise: Promise.resolve({
      numPages: pages.length,
      getPage: async (number: number) => fakePage(pages[number - 1]),
    }),
    destroy: pdf.destroy,
  }));
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue({ ok: true, arrayBuffer: async () => new ArrayBuffer(8) }),
  );
}

const LONG = 'Laravel dan Svelte membuat pengembangan aplikasi web terasa menyenangkan';

async function mountViewer(props: Record<string, unknown> = {}) {
  const result = render(PdfCanvasViewer, { url: '/doc.pdf', title: 'Dokumen', ...props });
  await vi.waitFor(() => expect(screen.queryByText('Memuat PDF…')).toBeNull());

  return result;
}

beforeEach(() => {
  loadDocument([[LONG, 'halaman satu'], [LONG, 'halaman dua'], [LONG, 'halaman tiga']]);
});

afterEach(() => {
  vi.useRealTimers();
});

describe('PdfCanvasViewer', () => {
  it('shows a loading message, then the document pages', async () => {
    render(PdfCanvasViewer, { url: '/doc.pdf', title: 'Dokumen' });

    expect(screen.getByText('Memuat PDF…')).toBeTruthy();
    await vi.waitFor(() => expect(screen.queryByText('Memuat PDF…')).toBeNull());

    expect(document.querySelectorAll('[data-page]').length).toBeGreaterThanOrEqual(3);
  });

  it('fetches the document from the given url with the worker configured', async () => {
    await mountViewer({ url: '/files/laporan.pdf' });

    expect(vi.mocked(fetch).mock.calls[0][0]).toBe('/files/laporan.pdf');
    expect(pdf.getDocument).toHaveBeenCalledOnce();
  });

  it('reports a load failure', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false, status: 404 }));
    render(PdfCanvasViewer, { url: '/missing.pdf' });

    expect(await screen.findByRole('alert')).toBeTruthy();
    expect(screen.getByText('PDF gagal dimuat.')).toBeTruthy();
  });

  it('destroys the loading task on unmount', async () => {
    const { unmount } = await mountViewer();

    unmount();

    expect(pdf.destroy).toHaveBeenCalled();
  });

  it('shows the title and a working close button', async () => {
    const onclose = vi.fn();
    await mountViewer({ onclose });

    expect(screen.getByText('Dokumen', { selector: '.pdf-title' })).toBeTruthy();

    await fireEvent.click(screen.getByLabelText('Tutup pratinjau'));

    expect(onclose).toHaveBeenCalledOnce();
  });

  it('zooms with the buttons within the limits', async () => {
    await mountViewer({ zoom: 1 });

    await fireEvent.click(screen.getByLabelText('Perbesar'));
    expect(screen.getByText('125%')).toBeTruthy();

    await fireEvent.click(screen.getByLabelText('Perkecil'));
    await fireEvent.click(screen.getByLabelText('Perkecil'));
    expect(screen.getByText('75%')).toBeTruthy();
  });

  it('disables zoom at the extremes', async () => {
    await mountViewer({ zoom: 4 });
    expect((screen.getByLabelText('Perbesar') as HTMLButtonElement).disabled).toBe(true);
  });

  it('picks a zoom preset', async () => {
    await mountViewer({ zoom: 1 });

    await fireEvent.click(screen.getByLabelText('Pilih zoom'));
    await fireEvent.click(screen.getByRole('menuitemradio', { name: '200%' }));

    expect(screen.getByText('200%', { selector: '.pdf-zoom-value' })).toBeTruthy();
  });

  it('toggles the thumbnail panel', async () => {
    await mountViewer();
    const toggle = screen.getByLabelText('Panel halaman');
    const initial = toggle.getAttribute('aria-pressed');

    await fireEvent.click(toggle);

    expect(toggle.getAttribute('aria-pressed')).not.toBe(initial);
  });

  it('switches the page layout', async () => {
    await mountViewer();

    await fireEvent.click(screen.getByLabelText('Tampilan halaman'));
    await fireEvent.click(screen.getByRole('menuitemradio', { name: /Halaman ganjil/ }));

    await fireEvent.click(screen.getByLabelText('Tampilan halaman'));
    expect(
      screen.getByRole('menuitemradio', { name: /Halaman ganjil/ }).getAttribute('aria-checked'),
    ).toBe('true');
  });

  it('offers rotate and download actions', async () => {
    await mountViewer({ url: '/doc.pdf' });

    await fireEvent.click(screen.getByLabelText('Menu'));
    expect(screen.getByRole('menuitem', { name: /Unduh/ }).getAttribute('href')).toBe('/doc.pdf');
  });

  it('toggles the pan tool', async () => {
    await mountViewer();
    const pan = screen.getByLabelText('Alat geser');
    const initial = pan.getAttribute('aria-pressed');

    await fireEvent.click(pan);

    expect(pan.getAttribute('aria-pressed')).not.toBe(initial);
  });

  describe('search', () => {
    async function openSearch() {
      await mountViewer();
      await fireEvent.click(screen.getByLabelText('Cari'));

      return screen.getByLabelText('Cari dalam dokumen') as HTMLInputElement;
    }

    it('finds text across pages and lists results', async () => {
      const input = await openSearch();

      await fireEvent.input(input, { target: { value: 'Svelte' } });

      expect(await screen.findByText(/dari 3 hasil/, {}, { timeout: 3000 })).toBeTruthy();
      expect(screen.getAllByText(/^Hal\. \d/)).toHaveLength(3);
    });

    it('reports when nothing matches', async () => {
      const input = await openSearch();

      await fireEvent.input(input, { target: { value: 'zzzzzzz' } });

      expect(await screen.findByText('Tidak ada hasil', {}, { timeout: 3000 })).toBeTruthy();
    });

    it('supports case-sensitive matching', async () => {
      const input = await openSearch();

      await fireEvent.click(screen.getByLabelText('Sesuai huruf besar/kecil'));
      await fireEvent.input(input, { target: { value: 'svelte' } });

      expect(await screen.findByText('Tidak ada hasil', {}, { timeout: 3000 })).toBeTruthy();
    });

    it('also searches annotations', async () => {
      const input = await openSearch();

      await fireEvent.input(input, { target: { value: 'Catatan penting' } });

      expect(await screen.findAllByText(/Anotasi/, {}, { timeout: 3000 })).not.toHaveLength(0);
    });

    it('steps through hits', async () => {
      const input = await openSearch();
      await fireEvent.input(input, { target: { value: 'Svelte' } });
      await screen.findByText(/dari 3 hasil/, {}, { timeout: 3000 });

      await fireEvent.click(screen.getByLabelText('Hasil berikutnya'));

      expect(screen.getByText(/2 dari 3 hasil/)).toBeTruthy();
    });
  });

  describe('electronic certificates', () => {
    function serveSigned(name: string): void {
      const bytes = readFileSync(join(process.cwd(), 'resources/js/tests/fixtures', name));
      const buffer = bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength);

      vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, arrayBuffer: async () => buffer }));
    }

    it('hides the certificate trigger when the PDF carries no signature', async () => {
      await mountViewer();

      expect(screen.queryByLabelText('Sertifikat Elektronik')).toBeNull();
    });

    it('lists the signer and issuer chain of a signed PDF in the sidebar', async () => {
      serveSigned('signed-komdigi.pdf');
      await mountViewer();

      await fireEvent.click(await screen.findByLabelText('Sertifikat Elektronik'));

      expect(await screen.findByText('BUDI CONTOH', { selector: 'strong' })).toBeTruthy();
      expect(screen.getByText('Root CA Indonesia (Kominfo/Komdigi)')).toBeTruthy();
      expect(screen.getByText('Dokumen tidak berubah sejak ditandatangani')).toBeTruthy();
    });

    it('offers the certificates from the mobile options menu', async () => {
      serveSigned('signed-foreign.pdf');
      await mountViewer();

      await fireEvent.click(screen.getByLabelText('Opsi'));
      await fireEvent.click(await screen.findByRole('menuitemcheckbox', { name: 'Sertifikat Elektronik' }));

      expect(await screen.findByText('Penerbit luar negeri (US)')).toBeTruthy();
    });
  });
});

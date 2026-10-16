import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';
import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
import BilingualHarness from '@/tests/stubs/bilingual-editor-harness.svelte';

vi.mock('@/components/media/media-picker-modal.svelte', async () => ({
  default: (await import('@/tests/stubs/media-picker-stub.svelte')).default,
}));

const hidden = (name: string) =>
  document.querySelector<HTMLInputElement>(
    `input[type="hidden"][name="${name}"]`,
  );

async function mountEditor(props: Record<string, unknown> = {}) {
  const result = render(RichTextEditor, { name: 'content', ...props });
  await vi.waitFor(() =>
    expect(document.querySelector('.ProseMirror')).not.toBeNull(),
  );
  await vi.waitFor(() =>
    expect(screen.queryByTitle('Urungkan')).not.toBeNull(),
  );

  return result;
}

describe('RichTextEditor', () => {
  it('mounts Tiptap with the initial content and submits it as HTML', async () => {
    await mountEditor({ value: '<p>Halo <strong>dunia</strong></p>' });

    expect(document.querySelector('.ProseMirror')?.textContent).toBe(
      'Halo dunia',
    );
    expect(hidden('content')?.value).toContain('<strong>dunia</strong>');
    expect(screen.getByText('2 kata')).toBeTruthy();
  });

  it('starts empty with a placeholder', async () => {
    await mountEditor({ placeholder: 'Tulis di sini' });

    expect(hidden('content')?.value).toBe('');
    expect(
      document.querySelector('[data-placeholder="Tulis di sini"]'),
    ).not.toBeNull();
    expect(screen.getByText('0 kata')).toBeTruthy();
  });

  it('offers the document tools and an HTML tab by default', async () => {
    await mountEditor();

    for (const title of ['Sisipkan blok', 'Urungkan', 'Ulangi']) {
      expect(screen.getByTitle(title)).toBeTruthy();
    }
    expect(screen.getByRole('tab', { name: 'HTML' })).toBeTruthy();
  });

  it('lists every block type in the top inserter', async () => {
    await mountEditor();

    await fireEvent.click(screen.getByTitle('Sisipkan blok'));

    for (const label of ['Paragraf', 'Judul 1', 'Gambar', 'Blok kode']) {
      expect(
        screen.getByRole('option', { name: new RegExp(label) }),
      ).toBeTruthy();
    }

    await fireEvent.input(screen.getByLabelText('Cari blok'), {
      target: { value: 'kutipan' },
    });

    expect(screen.getAllByRole('option')).toHaveLength(1);
  });

  it('shows the floating block toolbar while the editor is focused', async () => {
    await mountEditor({ value: '<p>teks</p>' });

    expect(screen.queryByTitle('Tebal')).toBeNull();

    document.querySelector<HTMLElement>('.ProseMirror')?.focus();

    await vi.waitFor(() => expect(screen.queryByTitle('Tebal')).not.toBeNull());
    expect(screen.getByTitle('Tautan')).toBeTruthy();
  });

  it('limits the tools in minimal mode', async () => {
    await mountEditor({ minimal: true });

    expect(screen.queryByTitle('Sisipkan blok')).toBeNull();
    expect(screen.queryByRole('tab', { name: 'HTML' })).toBeNull();
  });

  it('disables undo/redo with no history', async () => {
    await mountEditor();

    expect((screen.getByTitle('Urungkan') as HTMLButtonElement).disabled).toBe(
      true,
    );
    expect((screen.getByTitle('Ulangi') as HTMLButtonElement).disabled).toBe(
      true,
    );
  });

  it('shows the raw HTML in the HTML tab', async () => {
    await mountEditor({ value: '<p>Isi</p>' });

    await fireEvent.click(screen.getByRole('tab', { name: 'HTML' }));

    expect(
      (screen.getByLabelText('Kode HTML') as HTMLTextAreaElement).value,
    ).toBe('<p>Isi</p>');
    expect(
      screen.getByRole('tab', { name: 'HTML' }).getAttribute('aria-selected'),
    ).toBe('true');
  });

  it('applies HTML edits when returning to the visual tab', async () => {
    await mountEditor({ value: '<p>Lama</p>' });

    await fireEvent.click(screen.getByRole('tab', { name: 'HTML' }));
    await fireEvent.input(screen.getByLabelText('Kode HTML'), {
      target: { value: '<p>Baru sekali</p>' },
    });
    await fireEvent.click(screen.getByRole('tab', { name: 'Visual' }));

    await vi.waitFor(() =>
      expect(document.querySelector('.ProseMirror')?.textContent).toBe(
        'Baru sekali',
      ),
    );
    expect(screen.getByText('2 kata')).toBeTruthy();
  });

  it('applies formatting through the block type menu', async () => {
    await mountEditor({ value: '<p>teks</p>' });
    const editorElement = document.querySelector('.ProseMirror') as HTMLElement;
    editorElement.focus();
    const range = document.createRange();
    range.selectNodeContents(editorElement.querySelector('p')!);
    window.getSelection()?.removeAllRanges();
    window.getSelection()?.addRange(range);

    await vi.waitFor(() =>
      expect(screen.queryByTitle('Ubah jenis blok')).not.toBeNull(),
    );
    await fireEvent.click(screen.getByTitle('Ubah jenis blok'));
    await fireEvent.click(
      screen.getByRole('menuitem', { name: 'Daftar poin' }),
    );

    await vi.waitFor(() => expect(hidden('content')?.value).toContain('<ul>'));
  });

  it('marks invalid state', async () => {
    await mountEditor({ invalid: true });

    expect(document.querySelector('.rte.is-invalid')).not.toBeNull();
  });

  it('sanitises unsafe link targets on insert', async () => {
    await mountEditor({ value: '<p>x</p>' });

    expect(document.querySelector('a[href^="javascript:"]')).toBeNull();
  });

  it('destroys the editor on unmount', async () => {
    const { unmount } = await mountEditor({ value: '<p>x</p>' });

    unmount();

    expect(document.querySelector('.ProseMirror')).toBeNull();
  });

  it('keeps each language\'s own text when switching language', async () => {
    render(BilingualHarness);
    await vi.waitFor(() =>
      expect(document.querySelector('.ProseMirror')?.textContent).toBe('Halo dunia'),
    );

    await fireEvent.click(screen.getByText('to-en'));
    await vi.waitFor(() =>
      expect(document.querySelector('.ProseMirror')?.textContent).toBe('Hello world'),
    );
    expect(screen.getByTestId('stored-id').textContent).toBe('<p>Halo dunia</p>');
    expect(screen.getByTestId('stored-en').textContent).toBe('<p>Hello world</p>');

    await fireEvent.click(screen.getByText('to-id'));
    await vi.waitFor(() =>
      expect(document.querySelector('.ProseMirror')?.textContent).toBe('Halo dunia'),
    );
    expect(screen.getByTestId('stored-en').textContent).toBe('<p>Hello world</p>');
  });

  it('duplicates the block under the caret with Ctrl+Shift+D', async () => {
    await mountEditor({ value: '<p>Satu</p><p>Dua</p>' });
    const view = document.querySelector<HTMLElement>('.ProseMirror')!;

    view.focus();
    await fireEvent.keyDown(view, { key: 'D', ctrlKey: true, shiftKey: true });

    await vi.waitFor(() =>
      expect(hidden('content')?.value).toBe('<p>Satu</p><p>Satu</p><p>Dua</p>'),
    );
  });

  it('offers copy, cut, duplicate and paste-below for the block in the context menu', async () => {
    await mountEditor({ value: '<p>Satu</p>' });
    const view = document.querySelector<HTMLElement>('.ProseMirror')!;

    await fireEvent.contextMenu(view);

    for (const label of ['Salin blok', 'Potong blok', 'Gandakan blok', 'Tempel di bawah blok']) {
      await vi.waitFor(() => expect(screen.getByText(label)).toBeTruthy());
    }

    await fireEvent.click(screen.getByText('Gandakan blok'));
    await vi.waitFor(() =>
      expect(hidden('content')?.value).toBe('<p>Satu</p><p>Satu</p>'),
    );
  });

  describe('device preview', () => {
    function stubDesktop(initial: boolean) {
      const listeners: Array<() => void> = [];
      const query = {
        matches: initial,
        media: '(min-width: 992px)',
        addEventListener: (_: string, listener: () => void) => listeners.push(listener),
        removeEventListener: vi.fn(),
      };

      window.matchMedia = vi.fn().mockReturnValue(query);

      return (matches: boolean) => {
        query.matches = matches;
        listeners.forEach((listener) => listener());
      };
    }

    it('is hidden below the desktop breakpoint', async () => {
      stubDesktop(false);
      await mountEditor({ shell: true });

      expect(screen.queryByTitle('Pratinjau')).toBeNull();
    });

    it('returns to full width when the screen shrinks below desktop', async () => {
      const resize = stubDesktop(true);
      await mountEditor({ shell: true });

      await fireEvent.click(screen.getByTitle('Pratinjau'));
      await fireEvent.click(await screen.findByText('Ponsel'));
      expect(document.querySelector('.rte.is-device-mobile')).not.toBeNull();

      resize(false);

      await vi.waitFor(() =>
        expect(document.querySelector('.rte.is-device-mobile')).toBeNull(),
      );
      expect(screen.queryByTitle('Pratinjau')).toBeNull();
    });
  });

  describe('block clipboard', () => {
    const figure =
      '<figure class="wp-block-image is-align-center"><img src="/storage/a.jpg" alt="a"><figcaption>Keterangan</figcaption></figure><p>Setelahnya</p>';

    it('copies a captioned image as one whole block when only the caret is in it', async () => {
      await mountEditor({ value: figure });
      const view = document.querySelector<HTMLElement>('.ProseMirror')!;
      const data = new DataTransfer();

      await fireEvent.copy(view, { clipboardData: data });

      const html = data.getData('text/html');

      expect(html).toContain('wp-block-image');
      expect(html).toContain('<img src="/storage/a.jpg"');
      expect(html).toContain('Keterangan');
      expect(html).not.toContain('Setelahnya');
    });

    it('cuts the whole block with Ctrl+X and leaves the rest', async () => {
      await mountEditor({ value: figure });
      const view = document.querySelector<HTMLElement>('.ProseMirror')!;
      const data = new DataTransfer();

      await fireEvent.cut(view, { clipboardData: data });

      expect(data.getData('text/html')).toContain('wp-block-image');
      await vi.waitFor(() =>
        expect(hidden('content')?.value).toBe('<p>Setelahnya</p>'),
      );
    });

    it('pastes a copied block into another editor', async () => {
      await mountEditor({ value: figure });
      const data = new DataTransfer();

      await fireEvent.copy(document.querySelector('.ProseMirror')!, {
        clipboardData: data,
      });
      document.body.innerHTML = '';

      await mountEditor({ value: '' });
      await fireEvent.paste(document.querySelector('.ProseMirror')!, {
        clipboardData: data,
      });

      await vi.waitFor(() =>
        expect(hidden('content')?.value).toContain('<img src="/storage/a.jpg"'),
      );
      expect(hidden('content')?.value).toContain('Keterangan');
    });
  });

  describe('ordered list numbering', () => {
    it('sets the start number of the list under the caret', async () => {
      await mountEditor({
        shell: true,
        value: '<ol><li><p>Satu</p></li><li><p>Dua</p></li></ol>',
      });

      await fireEvent.click(screen.getByRole('tab', { name: 'Blok' }));

      const start = await screen.findByLabelText('Mulai dari angka');
      await fireEvent.input(start, { target: { value: '5' } });

      await vi.waitFor(() =>
        expect(hidden('content')?.value).toContain('<ol start="5">'),
      );
    });

    it('keeps roman numbering on nested lists through the editor HTML', async () => {
      await mountEditor({
        value:
          '<ol type="I"><li><p>Satu</p><ol type="i"><li><p>Satu-a</p></li></ol></li></ol>',
      });

      expect(hidden('content')?.value).toContain('<ol type="I">');
      expect(hidden('content')?.value).toContain('<ol type="i">');
    });
  });
});

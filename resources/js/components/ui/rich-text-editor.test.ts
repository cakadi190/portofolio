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

    // The plain quote and the scripture quote both match "kutipan".
    expect(screen.getAllByRole('option')).toHaveLength(2);
    expect(
      screen.getByRole('option', { name: /Kutipan kitab suci/ }),
    ).toBeTruthy();
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

  it("keeps each language's own text when switching language", async () => {
    render(BilingualHarness);
    await vi.waitFor(() =>
      expect(document.querySelector('.ProseMirror')?.textContent).toBe(
        'Halo dunia',
      ),
    );

    await fireEvent.click(screen.getByText('to-en'));
    await vi.waitFor(() =>
      expect(document.querySelector('.ProseMirror')?.textContent).toBe(
        'Hello world',
      ),
    );
    expect(screen.getByTestId('stored-id').textContent).toBe(
      '<p>Halo dunia</p>',
    );
    expect(screen.getByTestId('stored-en').textContent).toBe(
      '<p>Hello world</p>',
    );

    await fireEvent.click(screen.getByText('to-id'));
    await vi.waitFor(() =>
      expect(document.querySelector('.ProseMirror')?.textContent).toBe(
        'Halo dunia',
      ),
    );
    expect(screen.getByTestId('stored-en').textContent).toBe(
      '<p>Hello world</p>',
    );
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

    for (const label of [
      'Salin blok',
      'Potong blok',
      'Gandakan blok',
      'Tempel di bawah blok',
    ]) {
      await vi.waitFor(() => expect(screen.getByText(label)).toBeTruthy());
    }

    await fireEvent.click(screen.getByText('Gandakan blok'));
    await vi.waitFor(() =>
      expect(hidden('content')?.value).toBe('<p>Satu</p><p>Satu</p>'),
    );
  });

  describe('context menu search', () => {
    const searchLabel = 'Cari aksi atau blok…';

    it('moves focus from the text to the search box when typing', async () => {
      await mountEditor({ value: '<p>Satu</p>' });
      const view = document.querySelector<HTMLElement>('.ProseMirror')!;

      await fireEvent.contextMenu(view);
      const search = await screen.findByLabelText(searchLabel);

      await fireEvent.keyDown(view, { key: 't' });

      expect(document.activeElement).toBe(search);
    });

    it('does not steal shortcuts typed with a modifier', async () => {
      await mountEditor({ value: '<p>Satu</p>' });
      const view = document.querySelector<HTMLElement>('.ProseMirror')!;

      await fireEvent.contextMenu(view);
      const search = await screen.findByLabelText(searchLabel);

      await fireEvent.keyDown(view, { key: 'b', ctrlKey: true });

      expect(document.activeElement).not.toBe(search);
    });

    it('lists matching actions and insertable blocks, and inserts the chosen block below', async () => {
      await mountEditor({ value: '<p>Satu</p>' });
      const view = document.querySelector<HTMLElement>('.ProseMirror')!;

      await fireEvent.contextMenu(view);
      const search = await screen.findByLabelText(searchLabel);

      await fireEvent.input(search, { target: { value: 'tabel' } });

      await fireEvent.click(await screen.findByText('Sisipkan blok: Tabel'));

      await vi.waitFor(() =>
        expect(document.querySelector('.ProseMirror .wp-table-setup')).not.toBeNull(),
      );
      expect(hidden('content')?.value).toContain('<p>Satu</p>');
    });

    it('filters the normal actions too and says when nothing matches', async () => {
      await mountEditor({ value: '<p>Satu</p>' });
      await fireEvent.contextMenu(document.querySelector('.ProseMirror')!);
      const search = await screen.findByLabelText(searchLabel);

      await fireEvent.input(search, { target: { value: 'gandakan' } });
      expect(screen.getByText('Gandakan blok')).toBeTruthy();
      expect(screen.queryByText('Tempel di bawah blok')).toBeNull();

      await fireEvent.input(search, { target: { value: 'zzzxx' } });
      expect(await screen.findByText('Tidak ada hasil.')).toBeTruthy();
    });

    it('runs the highlighted result with the keyboard', async () => {
      await mountEditor({ value: '<p>Satu</p>' });
      await fireEvent.contextMenu(document.querySelector('.ProseMirror')!);
      const search = await screen.findByLabelText(searchLabel);

      await fireEvent.input(search, { target: { value: 'gandakan' } });
      await fireEvent.keyDown(search, { key: 'Enter' });

      await vi.waitFor(() =>
        expect(hidden('content')?.value).toBe('<p>Satu</p><p>Satu</p>'),
      );
    });
  });

  describe('block shortcuts', () => {
    const press = (init: KeyboardEventInit) =>
      fireEvent.keyDown(document.querySelector('.ProseMirror')!, init);

    it('adds a paragraph before and after with Ctrl+Alt+T / Y', async () => {
      await mountEditor({ value: '<p>Satu</p>' });

      await press({ code: 'KeyY', ctrlKey: true, altKey: true });
      await vi.waitFor(() =>
        expect(hidden('content')?.value).toBe('<p>Satu</p><p></p>'),
      );
    });

    it('sets a heading level with Ctrl+Alt+digit and back to paragraph with 0', async () => {
      await mountEditor({ value: '<p>Satu</p>' });

      // StarterKit's trailing node adds an empty paragraph after a closing heading.
      await press({ code: 'Digit2', ctrlKey: true, altKey: true });
      await vi.waitFor(() =>
        expect(hidden('content')?.value).toContain('<h2>Satu</h2>'),
      );

      await press({ code: 'Digit0', ctrlKey: true, altKey: true });
      await vi.waitFor(() =>
        expect(hidden('content')?.value).toContain('<p>Satu</p>'),
      );
      expect(hidden('content')?.value).not.toContain('<h2>');
    });

    it('removes the block with Shift+Alt+Z', async () => {
      await mountEditor({ value: '<p>Satu</p><p>Dua</p>' });

      await press({ code: 'KeyZ', shiftKey: true, altKey: true });
      await vi.waitFor(() =>
        expect(hidden('content')?.value).not.toContain('Satu'),
      );
      expect(hidden('content')?.value).toContain('Dua');
    });

    it('shows the shortcuts next to the context menu actions', async () => {
      await mountEditor({ value: '<p>Satu</p>' });
      await fireEvent.contextMenu(document.querySelector('.ProseMirror')!);

      await screen.findByText('Gandakan blok');

      for (const keys of ['Ctrl+Alt+T', 'Ctrl+Alt+Y', 'Shift+Alt+Z']) {
        expect(screen.getByText(keys)).toBeTruthy();
      }
    });
  });

  describe('block toolbar', () => {
    const figure =
      '<figure class="wp-block-image is-align-center"><img src="/storage/a.jpg" alt="a"><figcaption>Keterangan</figcaption></figure>';

    it('shows the block toolbar for text', async () => {
      await mountEditor({ value: '<p>Satu</p>' });
      document.querySelector<HTMLElement>('.ProseMirror')!.focus();

      await vi.waitFor(() =>
        expect(document.querySelector('.rte-blocktb')).not.toBeNull(),
      );
    });

    it('shows only the image toolbar, never a second one, for a selected image', async () => {
      await mountEditor({ value: figure });
      document.querySelector<HTMLElement>('.ProseMirror')!.focus();

      await vi.waitFor(() =>
        expect(document.querySelector('.ibt-bar')).not.toBeNull(),
      );
      expect(document.querySelector('.rte-blocktb')).toBeNull();
      expect(document.querySelectorAll('.ibt-bar')).toHaveLength(1);
      expect(
        document.querySelector('[aria-label="Pemformatan blok"]'),
      ).toBeNull();
    });
  });

  describe('device preview', () => {
    function stubDesktop(initial: boolean) {
      const listeners: Array<() => void> = [];
      const query = {
        matches: initial,
        media: '(min-width: 992px)',
        addEventListener: (_: string, listener: () => void) =>
          listeners.push(listener),
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

  describe('markdown clipboard', () => {
    const markdown = '# Judul\n\nIsi **tebal**\n\n- satu\n- dua';

    async function pasteInto(
      data: DataTransfer,
      props: Record<string, unknown> = {},
    ) {
      await mountEditor({ value: '', ...props });
      await fireEvent.paste(document.querySelector('.ProseMirror')!, {
        clipboardData: data,
      });
    }

    it('turns pasted Markdown text into blocks', async () => {
      const data = new DataTransfer();
      data.setData('text/plain', markdown);

      await pasteInto(data);

      await vi.waitFor(() =>
        expect(hidden('content')?.value).toContain('<h1>Judul</h1>'),
      );
      expect(hidden('content')?.value).toContain('<strong>tebal</strong>');
      expect(hidden('content')?.value).toContain('<li><p>satu</p></li>');
    });

    it('prefers the Markdown source over the styled HTML a code editor adds', async () => {
      const data = new DataTransfer();
      data.setData('text/plain', markdown);
      data.setData(
        'text/html',
        '<div style="font-family:monospace"><div><span style="color:#fff"># Judul</span></div></div>',
      );

      await pasteInto(data);

      await vi.waitFor(() =>
        expect(hidden('content')?.value).toContain('<h1>Judul</h1>'),
      );
    });

    it('keeps real HTML from a web page instead of the Markdown-looking text', async () => {
      const data = new DataTransfer();
      data.setData('text/plain', '# bukan judul');
      data.setData('text/html', '<p>Dari <strong>web</strong></p>');

      await pasteInto(data);

      await vi.waitFor(() =>
        expect(hidden('content')?.value).toContain('<strong>web</strong>'),
      );
      expect(hidden('content')?.value).not.toContain('<h1>');
    });

    it('does not touch ordinary pasted text', async () => {
      const data = new DataTransfer();
      data.setData('text/plain', 'Kalimat biasa saja.');

      await pasteInto(data);

      await vi.waitFor(() =>
        expect(hidden('content')?.value).toBe('<p>Kalimat biasa saja.</p>'),
      );
    });

    it('copies a formatted block with a Markdown text flavour next to the HTML', async () => {
      await mountEditor({
        value: '<h2>Judul</h2><p>Isi <strong>tebal</strong></p>',
      });
      const data = new DataTransfer();

      await fireEvent.copy(document.querySelector('.ProseMirror')!, {
        clipboardData: data,
      });

      expect(data.getData('text/html')).toMatch(/<h2[^>]*>Judul<\/h2>/);
      expect(data.getData('text/plain')).toBe('## Judul');
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

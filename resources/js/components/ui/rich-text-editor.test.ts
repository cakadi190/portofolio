import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';
import RichTextEditor from '@/components/ui/rich-text-editor.svelte';

vi.mock('@/components/media/media-picker-modal.svelte', async () => ({
  default: (await import('@/tests/stubs/media-picker-stub.svelte')).default,
}));

const hidden = (name: string) =>
  document.querySelector<HTMLInputElement>(`input[type="hidden"][name="${name}"]`);

async function mountEditor(props: Record<string, unknown> = {}) {
  const result = render(RichTextEditor, { name: 'content', ...props });
  await vi.waitFor(() => expect(document.querySelector('.ProseMirror')).not.toBeNull());
  await vi.waitFor(() => expect(screen.queryByTitle('Tebal')).not.toBeNull());

  return result;
}

describe('RichTextEditor', () => {
  it('mounts Tiptap with the initial content and submits it as HTML', async () => {
    await mountEditor({ value: '<p>Halo <strong>dunia</strong></p>' });

    expect(document.querySelector('.ProseMirror')?.textContent).toBe('Halo dunia');
    expect(hidden('content')?.value).toContain('<strong>dunia</strong>');
    expect(screen.getByText('2 kata')).toBeTruthy();
  });

  it('starts empty with a placeholder', async () => {
    await mountEditor({ placeholder: 'Tulis di sini' });

    expect(hidden('content')?.value).toBe('');
    expect(document.querySelector('[data-placeholder="Tulis di sini"]')).not.toBeNull();
    expect(screen.getByText('0 kata')).toBeTruthy();
  });

  it('offers the full toolbar by default', async () => {
    await mountEditor();

    for (const title of ['Tebal', 'Miring', 'Judul 1', 'Tautan', 'Sisipkan gambar dari pustaka media', 'Urungkan', 'Ulangi']) {
      expect(screen.getByTitle(title)).toBeTruthy();
    }
    expect(screen.getByRole('tab', { name: 'HTML' })).toBeTruthy();
  });

  it('limits the toolbar in minimal mode', async () => {
    await mountEditor({ minimal: true });

    expect(screen.getByTitle('Tebal')).toBeTruthy();
    expect(screen.queryByTitle('Judul 1')).toBeNull();
    expect(screen.queryByTitle('Tautan')).toBeNull();
    expect(screen.queryByRole('tab', { name: 'HTML' })).toBeNull();
  });

  it('disables undo/redo with no history', async () => {
    await mountEditor();

    expect((screen.getByTitle('Urungkan') as HTMLButtonElement).disabled).toBe(true);
    expect((screen.getByTitle('Ulangi') as HTMLButtonElement).disabled).toBe(true);
  });

  it('shows the raw HTML in the HTML tab', async () => {
    await mountEditor({ value: '<p>Isi</p>' });

    await fireEvent.click(screen.getByRole('tab', { name: 'HTML' }));

    expect((screen.getByLabelText('Kode HTML') as HTMLTextAreaElement).value).toBe('<p>Isi</p>');
    expect(screen.getByRole('tab', { name: 'HTML' }).getAttribute('aria-selected')).toBe('true');
  });

  it('applies HTML edits when returning to the visual tab', async () => {
    await mountEditor({ value: '<p>Lama</p>' });

    await fireEvent.click(screen.getByRole('tab', { name: 'HTML' }));
    await fireEvent.input(screen.getByLabelText('Kode HTML'), {
      target: { value: '<p>Baru sekali</p>' },
    });
    await fireEvent.click(screen.getByRole('tab', { name: 'Visual' }));

    await vi.waitFor(() =>
      expect(document.querySelector('.ProseMirror')?.textContent).toBe('Baru sekali'),
    );
    expect(screen.getByText('2 kata')).toBeTruthy();
  });

  it('applies formatting through toolbar commands', async () => {
    await mountEditor({ value: '<p>teks</p>' });
    const editorElement = document.querySelector('.ProseMirror') as HTMLElement;
    const range = document.createRange();
    range.selectNodeContents(editorElement.querySelector('p')!);
    window.getSelection()?.removeAllRanges();
    window.getSelection()?.addRange(range);

    await fireEvent.click(screen.getByTitle('Daftar poin'));

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
});

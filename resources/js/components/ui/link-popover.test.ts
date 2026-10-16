import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { fireEvent, render, screen } from '@testing-library/svelte';
import { afterEach, describe, expect, it, vi } from 'vitest';
import LinkPopover from '@/components/ui/link-popover.svelte';
import { LinkHighlight } from '@/lib/editor-blocks';

const instances: Editor[] = [];

function setup(content: string, selection: { from: number; to: number }) {
  const element = document.createElement('div');

  document.body.append(element);

  const editor = new Editor({
    element,
    extensions: [StarterKit.configure({ link: { openOnClick: false } }), LinkHighlight],
    content,
  });

  instances.push(editor);
  editor.commands.setTextSelection(selection);

  const host = document.createElement('div');
  const view = render(LinkPopover, { editor, host });

  return { editor, popover: view.component as { start(): void } };
}

afterEach(() => {
  instances.splice(0).forEach((editor) => editor.destroy());
  vi.unstubAllGlobals();
});

describe('LinkPopover', () => {
  it('links the selected text from a single URL field', async () => {
    const { editor, popover } = setup('<p>hello world</p>', { from: 1, to: 6 });

    popover.start();
    const input = await screen.findByPlaceholderText('Cari atau ketik URL');

    await fireEvent.input(input, { target: { value: 'example.com' } });
    await fireEvent.submit(input.closest('form') as HTMLFormElement);

    expect(editor.getHTML()).toContain('<a ');
    expect(editor.getHTML()).toContain('href="https://example.com"');
    expect(editor.getHTML()).toContain('>hello</a> world');
    expect(screen.queryByPlaceholderText('Cari atau ketik URL')).toBeNull();
  });

  it('rejects an address that is not a link', async () => {
    const { editor, popover } = setup('<p>hello</p>', { from: 1, to: 6 });

    popover.start();
    const input = await screen.findByPlaceholderText('Cari atau ketik URL');

    await fireEvent.input(input, { target: { value: 'javascript:alert(1)' } });
    await fireEvent.submit(input.closest('form') as HTMLFormElement);

    expect(await screen.findByText('Alamat tautan tidak valid.')).toBeTruthy();
    expect(editor.getHTML()).not.toContain('<a ');
  });

  it('previews an existing link and edits both its text and address', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false }));
    const { editor, popover } = setup('<p>go <a href="https://a.test/page">here</a> now</p>', {
      from: 5,
      to: 5,
    });

    popover.start();

    expect(await screen.findByText('a.test/page', { selector: '.rte-link-url' })).toBeTruthy();

    await fireEvent.click(screen.getByRole('button', { name: 'Ubah tautan' }));
    const text = (await screen.findByDisplayValue('here')) as HTMLInputElement;
    const url = screen.getByDisplayValue('https://a.test/page') as HTMLInputElement;

    await fireEvent.input(text, { target: { value: 'there' } });
    await fireEvent.input(url, { target: { value: 'https://b.test' } });
    await fireEvent.submit(text.closest('form') as HTMLFormElement);

    expect(editor.getHTML()).toContain('href="https://b.test"');
    expect(editor.getHTML()).toContain('>there</a>');
    expect(editor.getHTML()).not.toContain('>here<');
  });

  it('removes the link but keeps the text', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false }));
    const { editor, popover } = setup('<p><a href="https://a.test">label</a></p>', {
      from: 2,
      to: 2,
    });

    popover.start();
    await fireEvent.click(await screen.findByRole('button', { name: 'Hapus tautan' }));

    expect(editor.getHTML()).toBe('<p>label</p>');
  });

  it('closes on Escape without changing the text', async () => {
    const { editor, popover } = setup('<p>hello</p>', { from: 1, to: 6 });

    popover.start();
    const input = await screen.findByPlaceholderText('Cari atau ketik URL');

    await fireEvent.keyDown(input, { key: 'Escape' });

    expect(screen.queryByPlaceholderText('Cari atau ketik URL')).toBeNull();
    expect(editor.getHTML()).toBe('<p>hello</p>');
  });
});

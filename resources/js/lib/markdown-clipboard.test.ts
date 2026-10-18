import { Editor } from '@tiptap/core';
import { Markdown } from '@tiptap/markdown';
import { Slice } from '@tiptap/pm/model';
import StarterKit from '@tiptap/starter-kit';
import { describe, expect, it } from 'vitest';
import {
  BulletListStyle,
  Callout,
  CodeBlockHighlight,
  FigureImage,
  TableBlocks,
} from '@/lib/editor-blocks';
import {
  hasSemanticHtml,
  looksLikeMarkdown,
  markdownSlice,
  sliceToMarkdown,
} from '@/lib/markdown-clipboard';

function makeEditor(content = '') {
  return new Editor({
    element: document.createElement('div'),
    extensions: [
      StarterKit.configure({ codeBlock: false }),
      FigureImage,
      BulletListStyle,
      Callout,
      CodeBlockHighlight,
      ...TableBlocks,
      Markdown,
    ],
    content,
  });
}

function sliceOf(editor: Editor): Slice {
  return editor.state.doc.slice(0, editor.state.doc.content.size);
}

describe('looksLikeMarkdown', () => {
  it.each([
    '# Judul',
    '## Sub judul\n\nIsi',
    '- satu\n- dua',
    '1. satu\n2. dua',
    '> kutipan',
    '```js\nconst a = 1;\n```',
    '| a | b |\n| --- | --- |\n| 1 | 2 |',
    '---',
    'ini **tebal** dan `kode`',
    'baca [dokumen](https://a.test) dengan *miring*',
  ])('treats %j as Markdown', (text) => {
    expect(looksLikeMarkdown(text)).toBe(true);
  });

  it.each([
    '',
    '   ',
    'Kalimat biasa tanpa penanda.',
    '2 * 3 = 6',
    'harga #1 terbaik',
    'satu **tebal** saja',
    'lihat [tautan](https://a.test)',
    'snake_case_name dan file_name.ts',
  ])('keeps %j as plain text', (text) => {
    expect(looksLikeMarkdown(text)).toBe(false);
  });
});

describe('hasSemanticHtml', () => {
  it('recognises structure from pages, Word and this editor', () => {
    expect(hasSemanticHtml('<h1>Judul</h1>')).toBe(true);
    expect(hasSemanticHtml('<p>a <strong>b</strong></p>')).toBe(true);
    expect(hasSemanticHtml('<p data-pm-slice="1 1 []">a</p>')).toBe(true);
  });

  it('ignores the styled spans a code editor puts on the clipboard', () => {
    expect(
      hasSemanticHtml(
        '<meta charset="utf-8"><div style="font-family: monospace"><div><span style="color:#fff"># Judul</span></div></div>',
      ),
    ).toBe(false);
  });
});

describe('markdownSlice', () => {
  it('turns Markdown into this editor’s blocks', () => {
    const editor = makeEditor();
    const slice = markdownSlice(
      editor,
      '# Judul\n\nIsi **tebal**\n\n- satu\n- dua\n\n> kutipan',
    );

    expect(slice).not.toBeNull();

    editor.commands.setContent(slice!.content.toJSON());

    expect(editor.getHTML()).toContain('<h1>Judul</h1>');
    expect(editor.getHTML()).toContain('<strong>tebal</strong>');
    expect(editor.getHTML()).toContain('<li><p>satu</p></li>');
    expect(editor.getHTML()).toContain('<blockquote>');
  });

  it('reads tables', () => {
    const editor = makeEditor();
    const slice = markdownSlice(editor, '| a | b |\n| --- | --- |\n| 1 | 2 |');

    editor.commands.setContent(slice!.content.toJSON());

    expect(editor.getHTML()).toContain('<table');
    expect(editor.getHTML()).toContain('<th');
  });

  it('lifts an image out of its paragraph into the image block', () => {
    const editor = makeEditor();
    const slice = markdownSlice(
      editor,
      'Sebelum\n\n![logo](https://a.test/logo.png)\n\n**Sesudah** ya',
    );

    expect(slice).not.toBeNull();

    editor.commands.setContent(slice!.content.toJSON());

    const names = editor.state.doc.content.content.map((n) => n.type.name);

    expect(names).toContain('image');
    expect(editor.getHTML()).toContain('https://a.test/logo.png');
  });

  it('leaves plain text alone', () => {
    expect(markdownSlice(makeEditor(), 'Kalimat biasa saja.')).toBeNull();
  });
});

describe('sliceToMarkdown', () => {
  it('writes headings, marks, lists and links as Markdown', () => {
    const editor = makeEditor(
      '<h2>Judul</h2><p>Isi <strong>tebal</strong> dan <a href="https://a.test">tautan</a></p><ul><li><p>satu</p></li><li><p>dua</p></li></ul>',
    );
    const markdown = sliceToMarkdown(editor, sliceOf(editor));

    expect(markdown).toContain('## Judul');
    expect(markdown).toContain('**tebal**');
    expect(markdown).toContain('[tautan](https://a.test)');
    expect(markdown).toMatch(/^- satu$/m);
  });

  it('round-trips through markdownSlice', () => {
    const source = '# Judul\n\nIsi **tebal** dan *miring*\n\n1. satu\n2. dua';
    const editor = makeEditor();
    editor.commands.setContent(markdownSlice(editor, source)!.content.toJSON());

    expect(sliceToMarkdown(editor, sliceOf(editor))).toBe(source);
  });

  it('gives up on blocks Markdown cannot express, keeping the plain text flavour', () => {
    const editor = makeEditor(
      '<p>Biasa</p><div class="wp-block-callout"><p>Catatan</p></div>',
    );

    expect(editor.state.doc.content.content.map((n) => n.type.name)).toContain(
      'callout',
    );
    expect(sliceToMarkdown(editor, sliceOf(editor))).toBeNull();
  });

  it('returns null for an empty slice', () => {
    const editor = makeEditor();

    expect(sliceToMarkdown(editor, Slice.empty)).toBeNull();
  });
});

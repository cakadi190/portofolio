import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { describe, expect, it } from 'vitest';
import {
  ButtonBlock,
  Callout,
  CodeBlockHighlight,
  Definition,
  FigureImage,
  LinkCard,
  MathBlock,
  MathInline,
  Reference,
  References,
  SpecLabel,
  SpecRow,
  SpecSheet,
  SpecValue,
  TableBlocks,
  TableSetup,
  cleanBlockText,
  safeHref,
} from '@/lib/editor-blocks';

function editor(content: string) {
  return new Editor({
    element: document.createElement('div'),
    extensions: [
      StarterKit.configure({ codeBlock: false }),
      FigureImage,
      LinkCard,
      Callout,
      ButtonBlock,
      CodeBlockHighlight,
      Definition,
      SpecSheet,
      SpecRow,
      SpecLabel,
      SpecValue,
      References,
      Reference,
      MathInline,
      MathBlock,
      ...TableBlocks,
      TableSetup,
    ],
    content,
  });
}

describe('safeHref', () => {
  it.each([
    ['https://a.test', 'https://a.test'],
    ['  mailto:a@b.c ', 'mailto:a@b.c'],
    ['tel:123', 'tel:123'],
    ['/path', '/path'],
    ['#anchor', '#anchor'],
  ])('allows %s', (input, expected) => {
    expect(safeHref(input)).toBe(expected);
  });

  it.each(['javascript:alert(1)', 'data:text/html,x', '', undefined, 42])(
    'neutralises %s',
    (input) => {
      expect(safeHref(input)).toBe('#');
    },
  );
});

describe('FigureImage', () => {
  it('parses and re-renders a figure with caption, alignment and width', () => {
    const html =
      '<figure class="wp-block-image is-align-right" style="width: 40%"><img src="/a.webp" alt="A"><figcaption>Cap</figcaption></figure>';
    const instance = editor(html);

    const output = instance.getHTML();

    expect(output).toContain('is-align-right');
    expect(output).toContain('width: 40%');
    expect(output).toContain('<figcaption>Cap</figcaption>');
    instance.destroy();
  });

  it('keeps formatting marks inside the caption', () => {
    const instance = editor(
      '<figure class="wp-block-image is-align-center"><img src="/a.webp"><figcaption>Foto <strong>tebal</strong> <a href="https://x.id">tautan</a></figcaption></figure>',
    );

    const output = instance.getHTML();

    expect(output).toContain('<strong>tebal</strong>');
    expect(output).toContain('href="https://x.id"');
    instance.destroy();
  });

  it('still parses legacy plain images', () => {
    const instance = editor('<img src="/old.png">');

    expect(instance.getHTML()).toContain('wp-block-image is-align-center');
    instance.destroy();
  });
});

describe('FigureImage credit and source', () => {
  it('round-trips the photo credit and original source link', () => {
    const instance = editor(
      '<figure class="wp-block-image is-align-center" data-source="https://src.id/p"><img src="/a.webp"><figcaption data-credit="Workwear Archive">Editorial</figcaption></figure>',
    );

    const output = instance.getHTML();

    expect(output).toContain('data-source="https://src.id/p"');
    expect(output).toContain('data-credit="Workwear Archive"');
    instance.destroy();
  });

  it('drops a source that is not http(s)', () => {
    const instance = editor(
      '<figure class="wp-block-image" data-source="javascript:alert(1)"><img src="/a.webp"></figure>',
    );

    expect(instance.getHTML()).not.toContain('data-source');
    instance.destroy();
  });
});

describe('LinkCard', () => {
  it('parses and re-renders a link preview card', () => {
    const html =
      '<a class="wp-block-linkcard" href="https://blog.cloudflare.com/x" target="_blank" rel="noopener noreferrer"><img class="wp-linkcard-image" src="https://img.test/a.png" alt="" loading="lazy"><span class="wp-linkcard-meta"><span class="wp-linkcard-site">blog.cloudflare.com</span><strong class="wp-linkcard-title">Judul</strong></span></a>';
    const instance = editor(html);

    expect(instance.getHTML()).toBe(html);
    instance.destroy();
  });
});

describe('Callout', () => {
  it('round-trips the callout type and falls back to info', () => {
    const warning = editor(
      '<div class="wp-block-callout is-warning" data-type="warning"><p>x</p></div>',
    );
    const unknown = editor(
      '<div class="wp-block-callout" data-type="bogus"><p>x</p></div>',
    );

    expect(warning.getHTML()).toContain('is-warning');
    expect(unknown.getHTML()).toContain('is-info');
    warning.destroy();
    unknown.destroy();
  });
});

describe('ButtonBlock', () => {
  it('sanitises unsafe hrefs when rendering', () => {
    const instance = editor(
      '<a data-button href="javascript:alert(1)">Klik</a>',
    );

    expect(instance.getHTML()).not.toContain('javascript:');
    instance.destroy();
  });
});

describe('rich article blocks', () => {
  it('keeps a plain callout free of header markup and reads titles back', () => {
    const plain = editor(
      '<div class="wp-block-callout is-info" data-type="info"><p>x</p></div>',
    );

    expect(plain.getHTML()).not.toContain('wp-callout-head');
    plain.destroy();

    const titled = editor(
      '<div class="wp-block-callout is-warning" data-type="warning"><div class="wp-callout-head"><span class="wp-callout-label">Ekonomi</span><strong class="wp-callout-title">Biaya</strong></div><div class="wp-callout-body"><p>isi</p></div></div>',
    );
    const output = titled.getHTML();

    expect(output).toContain('wp-callout-label">Ekonomi<');
    expect(output).toContain('wp-callout-title">Biaya<');
    expect(output).toContain('<p>isi</p>');
    expect(output.match(/<p>isi<\/p>/g)).toHaveLength(1);
    titled.destroy();
  });

  it('round-trips a definition card', () => {
    const html =
      '<div class="wp-block-definition"><div class="wp-definition-head"><strong class="wp-definition-term">realm</strong><span class="wp-definition-phonetic">/rɛlm/</span></div><div class="wp-definition-body"><p>wilayah</p></div></div>';
    const instance = editor(html);
    const node = instance.state.doc.firstChild;

    expect(node?.type.name).toBe('definition');
    expect(node?.attrs).toMatchObject({ term: 'realm', phonetic: '/rɛlm/' });
    expect(instance.getHTML()).toContain('<p>wilayah</p>');
    instance.destroy();
  });

  it('round-trips a spec sheet with its rows', () => {
    const html =
      '<div class="wp-block-spec"><div class="wp-spec-title">Uniform</div><div class="wp-spec-rows"><div class="wp-spec-row"><div class="wp-spec-label">Bobot</div><div class="wp-spec-value">280 GSM</div></div><div class="wp-spec-row"><div class="wp-spec-label">Warna</div><div class="wp-spec-value">Hitam</div></div></div></div>';
    const instance = editor(html);
    const sheet = instance.state.doc.firstChild;

    expect(sheet?.type.name).toBe('specSheet');
    expect(sheet?.attrs.title).toBe('Uniform');
    expect(sheet?.childCount).toBe(2);
    expect(instance.getHTML()).toContain('wp-spec-value">280 GSM<');
    instance.destroy();
  });

  it('round-trips a reference list', () => {
    const instance = editor(
      '<div class="wp-block-references"><p class="wp-reference"><strong>A</strong> judul</p><p class="wp-reference">B</p></div>',
    );
    const list = instance.state.doc.firstChild;

    expect(list?.type.name).toBe('references');
    expect(list?.childCount).toBe(2);
    expect(instance.getHTML()).toContain(
      '<p class="wp-reference"><strong>A</strong> judul</p>',
    );
    instance.destroy();
  });

  it('stores equations as escaped LaTeX text', () => {
    const instance = editor(
      '<p>Teks <span class="wp-math">a &lt; b</span></p><div class="wp-math-block">E = mc^2</div>',
    );
    const [paragraph, block] = [
      instance.state.doc.child(0),
      instance.state.doc.child(1),
    ];

    expect(paragraph.child(1).type.name).toBe('mathInline');
    expect(paragraph.child(1).attrs.latex).toBe('a < b');
    expect(block.type.name).toBe('mathBlock');
    expect(instance.getHTML()).toContain(
      '<span class="wp-math">a &lt; b</span>',
    );
    expect(instance.getHTML()).toContain(
      '<div class="wp-math-block">E = mc^2</div>',
    );
    instance.destroy();
  });

  it('turns the table placeholder into a table of the requested size', () => {
    const host = document.createElement('div');
    const instance = new Editor({
      element: host,
      extensions: [StarterKit, ...TableBlocks, TableSetup],
      content: '<p>x</p>',
    });

    instance.commands.insertContent({ type: 'tableSetup' });

    const setup = host.querySelector('.wp-table-setup');

    expect(setup).not.toBeNull();
    setup!.querySelector<HTMLInputElement>('[name="cols"]')!.value = '3';
    setup!.querySelector<HTMLInputElement>('[name="rows"]')!.value = '4';
    setup!.querySelector('button')!.click();

    const html = instance.getHTML();

    expect(html).not.toContain('wp-table-setup');
    expect(html.match(/<tr/g)).toHaveLength(4);
    expect(html.match(/<th/g)).toHaveLength(3);
    instance.destroy();
  });

  it('inserts a table with a header row', () => {
    const instance = editor('<p>x</p>');

    instance.commands.insertTable({ rows: 2, cols: 2, withHeaderRow: true });

    const html = instance.getHTML();

    expect(html).toContain('<table');
    expect(html.match(/<th/g)?.length).toBe(2);
    expect(html.match(/<td/g)?.length).toBe(2);
    instance.destroy();
  });
});

describe('cleanBlockText', () => {
  it('strips markup characters and caps the length', () => {
    expect(cleanBlockText(' <b>"Hai"</b> ')).toBe('bHai/b');
    expect(cleanBlockText('x'.repeat(100))).toHaveLength(60);
    expect(cleanBlockText(null)).toBe('');
  });
});

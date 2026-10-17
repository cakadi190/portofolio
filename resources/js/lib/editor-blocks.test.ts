import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { describe, expect, it } from 'vitest';
import {
  BulletListStyle,
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
  ScriptureQuote,
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
      BulletListStyle,
      LinkCard,
      Callout,
      ButtonBlock,
      CodeBlockHighlight,
      Definition,
      ScriptureQuote,
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

describe('code block switches', () => {
  const codeEditor = (content: string) =>
    new Editor({
      element: document.createElement('div'),
      extensions: [StarterKit, CodeBlockHighlight],
      content,
    });

  it('round-trips the title-tab and line-number switches', () => {
    const instance = codeEditor(
      '<pre data-code-title-hidden="1" data-code-lines="1"><code>a\nb</code></pre>',
    );
    const attrs = instance.state.doc.firstChild?.attrs;

    expect(attrs).toMatchObject({ titleHidden: true, lineNumbers: true });
    expect(instance.getHTML()).toContain('data-code-title-hidden="1"');
    expect(instance.getHTML()).toContain('data-code-lines="1"');
  });

  it('keeps existing blocks titled and without numbers', () => {
    const instance = codeEditor('<pre><code>a</code></pre>');

    expect(instance.state.doc.firstChild?.attrs).toMatchObject({
      titleHidden: false,
      lineNumbers: false,
    });
    expect(instance.getHTML()).not.toContain('data-code-lines');
  });

  it('draws a gutter in the editor with one number per line', () => {
    const instance = codeEditor(
      '<pre data-code-lines="1"><code>a\nb\nc</code></pre>',
    );
    const gutter = instance.view.dom.querySelector('.code-card__gutter');

    expect(gutter?.textContent).toBe('1\n2\n3');
  });
});

describe('list styles', () => {
  it('keeps a known bullet marker and drops unknown ones', () => {
    const instance = editor(
      '<ul type="square"><li><p>a</p></li></ul><ul type="bogus"><li><p>b</p></li></ul><ul><li><p>c</p></li></ul>',
    );

    expect(instance.getHTML()).toBe(
      '<ul type="square"><li><p>a</p></li></ul><ul><li><p>b</p></li></ul><ul><li><p>c</p></li></ul>',
    );
  });

  it('keeps numbering type and start on nested ordered lists', () => {
    const instance = editor(
      '<ol type="I" start="3"><li><p>a</p><ol type="i"><li><p>b</p></li></ol></li></ol>',
    );

    expect(instance.getHTML()).toBe(
      '<ol start="3" type="I"><li><p>a</p><ol type="i"><li><p>b</p></li></ol></li></ol>',
    );
  });
});

describe('ScriptureQuote', () => {
  const quran =
    '<div class="wp-block-scripture is-quran" data-type="quran"><div class="wp-scripture-head"><span class="wp-scripture-type">Al-Qur\'an</span><span class="wp-scripture-reference">Ar-Ra\'d · 13:28</span></div><div class="wp-scripture-arabic" lang="ar" dir="rtl">أَلَا بِذِكْرِ اللَّهِ</div><div class="wp-scripture-body"><p>Ingatlah.</p></div><div class="wp-scripture-foot"><span class="wp-scripture-source">QS. Ar-Ra\'d [13]: 28</span></div></div>';

  it('round-trips kind, header, Arabic text, quotation and source', () => {
    const instance = editor(quran);
    const node = instance.state.doc.firstChild;

    expect(node?.type.name).toBe('scriptureQuote');
    expect(node?.attrs).toMatchObject({
      kind: 'quran',
      label: "Al-Qur'an",
      reference: "Ar-Ra'd · 13:28",
      arabic: 'أَلَا بِذِكْرِ اللَّهِ',
      source: "QS. Ar-Ra'd [13]: 28",
    });

    const output = instance.getHTML();

    expect(output).toContain('is-quran');
    expect(output).toContain('lang="ar" dir="rtl"');
    expect(output.match(/<p>Ingatlah\.<\/p>/g)).toHaveLength(1);
    instance.destroy();
  });

  it('falls back to the Bible kind and leaves out empty header, Arabic and source', () => {
    const instance = editor(
      '<div class="wp-block-scripture" data-type="bogus"><div class="wp-scripture-body"><p>Damai</p></div></div>',
    );
    const output = instance.getHTML();

    expect(output).toContain('is-bible');
    expect(output).not.toContain('wp-scripture-head');
    expect(output).not.toContain('wp-scripture-arabic');
    expect(output).not.toContain('wp-scripture-foot');
    instance.destroy();
  });
});

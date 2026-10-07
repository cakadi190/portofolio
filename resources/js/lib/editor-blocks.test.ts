import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { describe, expect, it } from 'vitest';
import {
  ButtonBlock,
  Callout,
  CodeBlockHighlight,
  FigureImage,
  safeHref,
} from '@/lib/editor-blocks';

function editor(content: string) {
  return new Editor({
    element: document.createElement('div'),
    extensions: [
      StarterKit.configure({ codeBlock: false }),
      FigureImage,
      Callout,
      ButtonBlock,
      CodeBlockHighlight,
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

  it('still parses legacy plain images', () => {
    const instance = editor('<img src="/old.png">');

    expect(instance.getHTML()).toContain('wp-block-image is-align-center');
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

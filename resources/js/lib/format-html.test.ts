import { describe, expect, it } from 'vitest';
import { formatHtml } from '@/lib/format-html';

describe('formatHtml', () => {
  it('puts block elements on their own lines and keeps inline content together', () => {
    expect(formatHtml('<h2>Judul</h2><p>Halo <strong>dunia</strong></p>')).toBe(
      '<h2>Judul</h2>\n<p>Halo <strong>dunia</strong></p>',
    );
  });

  it('indents nested blocks', () => {
    expect(formatHtml('<ul><li>Satu</li><li>Dua</li></ul>')).toBe(
      '<ul>\n  <li>Satu</li>\n  <li>Dua</li>\n</ul>',
    );
  });

  it('leaves pre blocks untouched', () => {
    const pre = '<pre><code>a\n  b</code></pre>';

    expect(formatHtml(`<div>${pre}</div>`)).toBe(`<div>\n  ${pre}\n</div>`);
  });

  it('collapses stray whitespace and is stable when run twice', () => {
    const once = formatHtml('<p>a \n\n  b</p>\n\n<hr><p>c</p>');

    expect(once).toBe('<p>a b</p>\n<hr>\n<p>c</p>');
    expect(formatHtml(once)).toBe(once);
  });
});

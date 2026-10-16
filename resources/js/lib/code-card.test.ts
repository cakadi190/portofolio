import { describe, expect, it } from 'vitest';
import {
  codeLineCount,
  codeText,
  enhanceCodeCards,
  lineNumbersText,
} from '@/lib/code-card';

function render(attributes = '', code = 'a\nb\nc'): HTMLElement {
  const root = document.createElement('div');

  root.innerHTML = `<pre ${attributes}><code>${code}</code></pre>`;
  enhanceCodeCards(root);

  return root;
}

describe('code card helpers', () => {
  it('counts lines without a trailing newline', () => {
    expect(codeLineCount('a\nb\n')).toBe(2);
    expect(codeLineCount('single')).toBe(1);
    expect(lineNumbersText('a\nb\nc')).toBe('1\n2\n3');
  });
});

describe('enhanceCodeCards', () => {
  it('wraps a block with a title tab and no gutter by default', () => {
    const root = render('data-code-title="User.php"');

    expect(root.querySelector('.code-card__title')?.textContent).toBe(
      'User.php',
    );
    expect(root.querySelector('.code-card--untitled')).toBeNull();
    expect(root.querySelector('.code-card__gutter')).toBeNull();
  });

  it('falls back to the default title', () => {
    expect(render().querySelector('.code-card__title')?.textContent).toBe(
      'Kode',
    );
  });

  it('drops the title tab when it is switched off', () => {
    const root = render(
      'data-code-title-hidden="1" data-code-title="User.php"',
    );

    expect(root.querySelector('.code-card--untitled')).not.toBeNull();
    expect(root.querySelector('.code-card__title')).toBeNull();
    expect(root.querySelector('.code-card__copy')).not.toBeNull();
  });

  it('adds one number per line and keeps them out of the copied code', () => {
    const root = render('data-code-lines="1"');
    const pre = root.querySelector('pre') as HTMLElement;

    expect(root.querySelector('.code-card__gutter')?.textContent).toBe(
      '1\n2\n3',
    );
    expect(codeText(pre)).toBe('a\nb\nc');
  });

  it('is idempotent', () => {
    const root = render('data-code-lines="1"');

    enhanceCodeCards(root);

    expect(root.querySelectorAll('.code-card')).toHaveLength(1);
    expect(root.querySelectorAll('.code-card__gutter')).toHaveLength(1);
  });
});

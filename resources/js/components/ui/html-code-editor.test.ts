import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it } from 'vitest';
import HtmlCodeEditor from '@/components/ui/html-code-editor.svelte';

const area = () => screen.getByLabelText('Kode HTML') as HTMLTextAreaElement;

describe('HtmlCodeEditor', () => {
  it('shows line numbers, highlighted tags and the cursor position', async () => {
    render(HtmlCodeEditor, { value: '<p>a</p>\n<p>b</p>' });

    expect(document.querySelectorAll('.hce-gutter span')).toHaveLength(2);
    expect(document.querySelector('.hce-pre .hljs-tag')).not.toBeNull();
    expect(screen.getByText('Baris 1, Kolom 1')).toBeTruthy();
  });

  it('formats the code with the Format button', async () => {
    render(HtmlCodeEditor, { value: '<ul><li>Satu</li></ul>' });

    await fireEvent.click(screen.getByTitle(/Format kode/));

    expect(area().value).toBe('<ul>\n  <li>Satu</li>\n</ul>');
  });

  it('wraps the selection in a tag', async () => {
    render(HtmlCodeEditor, { value: 'halo' });
    area().setSelectionRange(0, 4);

    await fireEvent.click(screen.getByTitle('Tebal'));

    expect(area().value).toBe('<strong>halo</strong>');
  });

  it('indents with Tab instead of leaving the field', async () => {
    render(HtmlCodeEditor, { value: 'x' });
    area().setSelectionRange(0, 0);

    await fireEvent.keyDown(area(), { key: 'Tab' });

    expect(area().value).toBe('  x');
  });
});

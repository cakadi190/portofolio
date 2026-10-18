import { beforeEach, describe, expect, it, vi } from 'vitest';
import { enhanceCodeCards } from '@/lib/code-card';
import { renderMermaidIn, renderMermaidInto } from '@/lib/mermaid';

const mermaid = vi.hoisted(() => ({
  initialize: vi.fn(),
  parse: vi.fn(),
  render: vi.fn(),
}));

vi.mock('mermaid', () => ({ default: mermaid }));

describe('renderMermaidInto', () => {
  beforeEach(() => {
    mermaid.parse.mockResolvedValue(true);
    mermaid.render.mockResolvedValue({ svg: '<svg data-diagram="1"></svg>' });
  });

  it('draws the diagram and marks the element as rendered', async () => {
    const element = document.createElement('pre');

    renderMermaidInto(element, 'flowchart TD\n  A --> B');

    expect(element.textContent).toBe('flowchart TD\n  A --> B');

    await vi.waitFor(() =>
      expect(element.classList.contains('is-rendered')).toBe(true),
    );
    expect(element.querySelector('svg[data-diagram="1"]')).not.toBeNull();
    expect(mermaid.initialize).toHaveBeenCalledWith(
      expect.objectContaining({ securityLevel: 'strict', startOnLoad: false }),
    );
  });

  it('keeps the source and flags syntax errors without rendering', async () => {
    mermaid.parse.mockResolvedValue(false);
    const element = document.createElement('pre');

    renderMermaidInto(element, 'flowchart ???');

    await vi.waitFor(() =>
      expect(element.classList.contains('is-invalid')).toBe(true),
    );
    expect(element.textContent).toBe('flowchart ???');
    expect(mermaid.render).not.toHaveBeenCalled();
  });

  it('falls back to the source when rendering throws', async () => {
    mermaid.render.mockRejectedValue(new Error('boom'));
    const element = document.createElement('pre');

    renderMermaidInto(element, 'pie\n  "a": 1');

    await vi.waitFor(() =>
      expect(element.classList.contains('is-invalid')).toBe(true),
    );
    expect(element.textContent).toBe('pie\n  "a": 1');
  });

  it('ignores a stale render when the source changed meanwhile', async () => {
    const element = document.createElement('pre');

    renderMermaidInto(element, 'flowchart TD\n  A --> B');
    renderMermaidInto(element, 'flowchart TD\n  X --> Y');

    await vi.waitFor(() =>
      expect(element.classList.contains('is-rendered')).toBe(true),
    );
    expect(element.dataset.mermaid).toBe('flowchart TD\n  X --> Y');
  });

  it('does not draw an empty diagram', () => {
    mermaid.render.mockClear();

    renderMermaidInto(document.createElement('pre'), '   ');

    expect(mermaid.render).not.toHaveBeenCalled();
  });
});

describe('renderMermaidIn', () => {
  it('draws each diagram of rendered article HTML once', async () => {
    mermaid.parse.mockResolvedValue(true);
    mermaid.render.mockClear();
    mermaid.render.mockResolvedValue({ svg: '<svg></svg>' });

    const root = document.createElement('div');
    root.innerHTML =
      '<pre class="wp-mermaid">flowchart TD\n  A --> B</pre><pre><code>x</code></pre>';

    renderMermaidIn(root);
    renderMermaidIn(root);

    await vi.waitFor(() =>
      expect(
        root.querySelector('.wp-mermaid')?.classList.contains('is-rendered'),
      ).toBe(true),
    );
    expect(mermaid.render).toHaveBeenCalledTimes(1);
  });

  it('is not wrapped in a code card with the other pre blocks', () => {
    const root = document.createElement('div');
    root.innerHTML =
      '<pre class="wp-mermaid">pie</pre><pre><code>x</code></pre>';

    enhanceCodeCards(root);

    expect(root.querySelectorAll('.code-card')).toHaveLength(1);
    expect(root.querySelector('.wp-mermaid')?.parentElement).toBe(root);
  });
});

import { fireEvent, render, screen } from '@testing-library/svelte';
import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import LazyRichTextEditor from '@/components/ui/lazy-rich-text-editor.svelte';

const props = {
  name: 'message',
  value: '<p>Halo</p>',
  minimal: true,
  placeholder: 'Tulis pesan',
};

const hiddenInput = (container: HTMLElement) =>
  container.querySelector<HTMLInputElement>('input[type="hidden"][name="message"]');

// The editor chunk is large; transforming it for the first time can exceed the
// default timeout when the whole suite runs in parallel, so warm it up once.
beforeAll(async () => {
  await import('@/components/ui/rich-text-editor.svelte');
}, 60_000);

afterEach(() => vi.unstubAllGlobals());

describe('LazyRichTextEditor', () => {
  it('shows a same-sized skeleton with a submittable hidden input until the field is near the viewport', () => {
    const { container } = render(LazyRichTextEditor, props);

    const skeleton = container.querySelector('.lazy-rte');
    expect(skeleton?.getAttribute('aria-busy')).toBe('true');
    expect(skeleton?.classList.contains('lazy-rte-minimal')).toBe(true);
    expect(screen.getByText('Tulis pesan')).toBeTruthy();
    expect(hiddenInput(container)?.value).toBe('<p>Halo</p>');
    expect(container.querySelector('.rte')).toBeNull();
  });

  it('swaps in the editor once the field intersects the viewport', async () => {
    vi.stubGlobal(
      'IntersectionObserver',
      class {
        constructor(private callback: (entries: { isIntersecting: boolean }[]) => void) {}
        observe() {
          this.callback([{ isIntersecting: true }]);
        }
        disconnect() {}
      },
    );

    const { container } = render(LazyRichTextEditor, props);

    await vi.waitFor(() => expect(container.querySelector('.rte')).not.toBeNull(), { timeout: 15_000 });
    expect(container.querySelector('.lazy-rte')).toBeNull();
    expect(hiddenInput(container)).not.toBeNull();
  });

  it('loads the editor when the skeleton receives focus', async () => {
    const { container } = render(LazyRichTextEditor, props);

    await fireEvent.focusIn(container.querySelector('.lazy-rte') as HTMLElement);

    await vi.waitFor(() => expect(container.querySelector('.rte')).not.toBeNull(), { timeout: 15_000 });
  });
});

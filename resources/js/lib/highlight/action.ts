import type { Action } from 'svelte/action';
import { enhanceCodeCards } from '@/lib/code-card';
import { enhanceFigures } from '@/lib/figure-card';
import { renderMathIn } from '@/lib/math';
import { highlightAll } from './index';

/**
 * Svelte action that highlights the `pre code` blocks and draws the
 * equations inside rendered WYSIWYG HTML. Pass the HTML so blocks are highlighted again when it changes.
 *
 * @example <div use:highlightCode={post.content}>{@html post.content}</div>
 */
export const highlightCode: Action<HTMLElement, string | null | undefined> = (
  node,
) => {
  highlightAll(node);
  enhanceCodeCards(node);
  renderMathIn(node);
  enhanceFigures(node);

  return {
    update() {
      highlightAll(node);
      enhanceCodeCards(node);
      renderMathIn(node);
      enhanceFigures(node);
    },
  };
};

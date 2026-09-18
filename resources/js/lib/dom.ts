import type { Action } from 'svelte/action';

/**
 * Svelte action that moves the node to `<body>` (or a custom target) and
 * removes it again on destroy.
 *
 * Useful when an ancestor creates its own stacking context (e.g. `main`), which
 * would otherwise trap modals, popovers or toasts under body-level overlays
 * such as Bootstrap's modal backdrop.
 *
 * @example <div use:portal class="modal">…</div>
 * @example <div use:portal={'#modals'} class="modal">…</div>
 */
export const portal: Action<HTMLElement, HTMLElement | string | undefined> = (
  node,
  target,
) => {
  const resolveTarget = (
    value: HTMLElement | string | undefined,
  ): HTMLElement =>
    (typeof value === 'string'
      ? document.querySelector<HTMLElement>(value)
      : value) ?? document.body;

  resolveTarget(target).appendChild(node);

  return {
    update(nextTarget) {
      resolveTarget(nextTarget).appendChild(node);
    },
    destroy() {
      node.remove();
    },
  };
};

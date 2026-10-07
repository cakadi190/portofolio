import PerfectScrollbar from 'perfect-scrollbar';
import 'perfect-scrollbar/css/perfect-scrollbar.css';

/**
 * Svelte action that swaps a node's native scrollbar for perfect-scrollbar and keeps it
 * in sync when the node or its children resize or change.
 */
export function perfectScrollbar(
  node: HTMLElement,
  options: PerfectScrollbar.Options = {},
) {
  const instance = new PerfectScrollbar(node, {
    wheelPropagation: true,
    ...options,
  });
  const refresh = () => instance.update();
  const resizeObserver = new ResizeObserver(refresh);
  const mutationObserver = new MutationObserver(() => {
    Array.from(node.children).forEach((child) => resizeObserver.observe(child));
    refresh();
  });

  resizeObserver.observe(node);
  Array.from(node.children).forEach((child) => resizeObserver.observe(child));
  mutationObserver.observe(node, { childList: true });

  return {
    destroy() {
      mutationObserver.disconnect();
      resizeObserver.disconnect();
      instance.destroy();
    },
  };
}

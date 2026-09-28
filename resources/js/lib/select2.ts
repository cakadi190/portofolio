import type { Action } from 'svelte/action';
import $ from 'jquery';
// @ts-expect-error - select2's dist file has no bundler-friendly module typings
import select2Factory from 'select2/dist/js/select2.js';
import 'select2/dist/js/i18n/id.js';

declare global {
  interface Window {
    jQuery?: typeof $;
  }
}

// select2's UMD wrapper only self-registers onto a *global* jQuery; under
// Vite's ESM bundling it never finds one, so the factory has to be invoked
// manually, once, before any `.select2()` call.
if (!$.fn.select2) {
  window.jQuery = $;
  select2Factory(window, $);
}

/**
 * Applies select2 (themed for Bootstrap 5) to a native `<select>`. Keeps the
 * underlying element as the source of truth so Inertia's `<Form>` (which
 * reads native form fields on submit) needs no other wiring.
 */
export const select2: Action<HTMLSelectElement, JQuery.Select2Options | undefined> = (
  node,
  options,
) => {
  const $node = $(node);
  const dropdownParent = $node.closest('.modal').length
    ? $node.closest('.modal')
    : $(document.body);

  $node.select2({
    theme: 'bootstrap-5',
    language: 'id',
    width: '100%',
    dropdownParent,
    ...options,
  });

  return {
    destroy() {
      if ($node.data('select2')) {
        $node.select2('destroy');
      }
    },
  };
};

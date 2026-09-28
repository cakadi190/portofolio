import './jquery-global';
import type { Action } from 'svelte/action';
import $ from 'jquery';
import 'select2/dist/js/i18n/id.js';

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

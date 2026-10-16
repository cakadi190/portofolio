<script lang="ts">
  import { t } from '@/lib/i18n';
  import SvelteSelect from 'svelte-select';

  type Value = string | number;
  type Option = { value: Value; label: string };

  /**
   * Bootstrap-themed searchable select built on svelte-select (v6).
   *
   * - Inside an Inertia `<Form>`: pass `name`; a hidden `<input {name}>` is
   *   rendered so native `FormData` collects the value.
   * - Outside a form: `bind:value` and/or `onchange` (called with the new
   *   value, or `null` when cleared). Both selecting an item and clearing go
   *   through `onchange`, unlike svelte-select which splits them in two.
   *
   * With `required` (and no initial value) the first option is preselected,
   * matching a native `<select>`; otherwise the value may be cleared and the
   * `placeholder` doubles as the "all / default" label.
   */
  let {
    id,
    name,
    items,
    value = $bindable(null),
    required = false,
    invalid = false,
    searchable = true,
    placeholder,
    ariaLabel,
    size = 'md',
    inline = false,
    disabled = false,
    class: className = '',
    onchange,
  }: {
    id?: string;
    name?: string;
    items: Option[];
    value?: Value | null;
    required?: boolean;
    invalid?: boolean;
    searchable?: boolean;
    placeholder?: string;
    ariaLabel?: string;
    size?: 'sm' | 'md';
    inline?: boolean;
    disabled?: boolean;
    class?: string;
    onchange?: (value: Value | null) => void;
  } = $props();

  let selected = $state<Value | null>(null);

  $effect(() => {
    selected = value ?? (required ? (items[0]?.value ?? null) : null);
  });

  function commit(next: Value | null | undefined): void {
    value = next ?? null;
    onchange?.(value);
  }
</script>

<div
  class="neo-select {className}"
  class:is-invalid={invalid}
  class:is-sm={size === 'sm'}
  class:is-inline={inline}
>
  <SvelteSelect
    {id}
    {name}
    {items}
    {searchable}
    placeholder={placeholder ?? t('select.placeholder')}
    {disabled}
    bind:value={selected}
    valueMode="id"
    clearable={!required}
    hasError={invalid}
    inputAttributes={ariaLabel ? { 'aria-label': ariaLabel } : {}}
    floatingConfig={{ strategy: 'fixed' }}
    onchange={(next) => commit(next as Value | null)}
    onclear={() => commit(null)}
  />
</div>

<style>
  .neo-select {
    --height: calc(1.5em + 0.75rem + var(--neo-border-width, 1px) * 2);
    --font-size: 1rem;
    --background: var(--neo-body-bg);
    --border: var(--neo-border-width, 1px) solid var(--neo-border-color);
    --border-hover: var(--neo-border-width, 1px) solid var(--neo-border-color);
    --border-focused: var(--neo-border-width, 1px) solid var(--neo-primary);
    --border-radius: var(--neo-border-radius, 0.5rem);
    --padding: 0 0.75rem;
    --input-color: var(--neo-body-color);
    --item-color: var(--neo-body-color);
    --selected-item-color: var(--neo-body-color);
    --placeholder-color: var(--neo-secondary-color);
    --error-border: var(--neo-border-width, 1px) solid var(--neo-danger);
    --error-background: var(--neo-body-bg);
    --list-background: var(--neo-body-bg);
    --list-border: var(--neo-border-width, 1px) solid var(--neo-border-color);
    --list-border-radius: var(--neo-border-radius, 0.5rem);
    --list-z-index: 1080;
    --item-hover-bg: var(--neo-tertiary-bg);
    --item-active-background: var(--neo-primary);
    --item-is-active-bg: var(--neo-primary);
    --item-is-active-color: #fff;
    --icons-color: var(--neo-secondary-color);
    --clear-select-color: var(--neo-secondary-color);
    width: 100%;
  }

  .neo-select.is-sm {
    --height: calc(1.5em + 0.5rem + var(--neo-border-width, 1px) * 2);
    --font-size: 0.875rem;
    --padding: 0 0.5rem;
  }

  .neo-select.is-inline {
    width: auto;
    min-width: 11rem;
  }
</style>

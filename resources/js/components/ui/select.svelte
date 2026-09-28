<script lang="ts">
  import SvelteSelect from 'svelte-select';

  type Option = { value: string | number; label: string };

  /**
   * Bootstrap-themed searchable select built on svelte-select. Renders a
   * hidden `<input {name}>` so Inertia's `<Form>` collects the value from
   * native `FormData` like any other field.
   *
   * With `required` (and no initial value) the first option is preselected,
   * matching a native `<select>`; otherwise an empty value is allowed.
   */
  let {
    id,
    name,
    items,
    value = null,
    required = false,
    invalid = false,
    searchable = true,
    placeholder = 'Pilih…',
  }: {
    id?: string;
    name: string;
    items: Option[];
    value?: string | number | null;
    required?: boolean;
    invalid?: boolean;
    searchable?: boolean;
    placeholder?: string;
  } = $props();

  let selected = $state<string | number | null>(null);

  $effect(() => {
    selected = value ?? (required ? (items[0]?.value ?? null) : null);
  });
</script>

<div class="neo-select" class:is-invalid={invalid}>
  <SvelteSelect
    {id}
    {name}
    {items}
    {searchable}
    {placeholder}
    bind:value={selected}
    valueMode="id"
    clearable={!required}
    hasError={invalid}
    floatingConfig={{ strategy: 'fixed' }}
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
</style>

<script lang="ts">
  import { t } from '@/lib/i18n';
  import Plus from '@lucide/svelte/icons/plus';
  import X from '@lucide/svelte/icons/x';
  import IconPickerModal, { type IconResult } from '@/components/icons/icon-picker-modal.svelte';
  import { iconUrl, techIcon } from '@/lib/tech-icon';

  export type PickedTechnology = { id: number; name: string; icon: string | null };

  /**
   * Technologies of a portfolio, picked from the icon search. Submitted as `name[]`: the
   * Iconify id for a new pick, or the record id for older technologies saved without one.
   */
  let {
    name,
    selected = [],
  }: {
    name: string;
    selected?: PickedTechnology[];
  } = $props();

  type Item = { value: string; label: string; icon: string };

  // svelte-ignore state_referenced_locally
  let items = $state<Item[]>(
    selected.map((technology) => ({
      value: technology.icon ?? String(technology.id),
      label: technology.name,
      icon: technology.icon ?? techIcon(technology.name),
    })),
  );
  let open = $state(false);

  function add(icons: IconResult[]): void {
    const fresh = icons
      .filter((icon) => !items.some((item) => item.icon === icon.id))
      .map((icon) => ({ value: icon.id, label: icon.name, icon: icon.id }));

    items = [...items, ...fresh];
  }
</script>

<div class="d-flex flex-wrap gap-2 mb-2">
  {#each items as item (item.value)}
    <span class="badge text-bg-light border d-inline-flex align-items-center gap-2 fw-normal">
      <img src={iconUrl(item.icon)} alt="" width="16" height="16" />
      {item.label}
      <button
        type="button"
        class="btn-close-inline"
        aria-label={t('ui.hapus_item', { name: item.label })}
        onclick={() => (items = items.filter((other) => other.value !== item.value))}
      >
        <X size={12} />
      </button>
      <input type="hidden" name={`${name}[]`} value={item.value} />
    </span>
  {/each}
</div>

<button type="button" class="btn btn-sm btn-outline-primary" onclick={() => (open = true)}>
  <Plus size={14} />
  {t('ui.tambah_teknologi')}
</button>

<IconPickerModal bind:open multiple title={t('ui.tambah_teknologi')} onSelect={add} />

<style>
  .btn-close-inline {
    display: inline-flex;
    padding: 0;
    color: inherit;
    background: none;
    border: 0;
    opacity: 0.6;
  }

  .btn-close-inline:hover {
    opacity: 1;
  }
</style>

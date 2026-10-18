<script lang="ts">
  import { t } from '@/lib/i18n';
  import { untrack } from 'svelte';
  import Check from '@lucide/svelte/icons/check';
  import FormModal from '@/components/form-modal.svelte';
  import { search as searchIcons } from '@/wayfinder/routes/admin/icons';

  export type IconResult = { id: string; name: string; url: string };

  /**
   * Icon search backed by the Iconify API (the data behind icones.js.org). The server
   * proxies and caches the search, and keeps a local copy of each icon once it is used.
   *
   * `multiple` keeps ticking icons until confirmed; otherwise a click picks one and closes.
   */
  let {
    open = $bindable(false),
    title = t('ui.pilih_ikon'),
    multiple = false,
    initialSelection = [],
    onSelect,
  }: {
    open?: boolean;
    title?: string;
    multiple?: boolean;
    initialSelection?: IconResult[];
    onSelect: (icons: IconResult[]) => void;
  } = $props();

  let query = $state('');
  let results = $state<IconResult[]>([]);
  let selected = $state<IconResult[]>([]);
  let loading = $state(false);
  let failed = $state(false);
  let timer: ReturnType<typeof setTimeout> | undefined;
  let controller: AbortController | undefined;

  async function load(): Promise<void> {
    controller?.abort();
    const term = query.trim();

    if (term === '') {
      results = [];
      loading = false;

      return;
    }

    const current = new AbortController();
    controller = current;
    loading = true;
    failed = false;

    try {
      const response = await fetch(searchIcons({ query: { query: term } }).url, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
        signal: current.signal,
      });

      if (!response.ok) {
        throw new Error();
      }

      results = ((await response.json()) as { data: IconResult[] }).data;
    } catch (exception) {
      if (!current.signal.aborted && !(exception instanceof DOMException)) {
        failed = true;
      }
    } finally {
      if (controller === current) {
        loading = false;
      }
    }
  }

  $effect(() => {
    if (open) {
      untrack(() => {
        selected = initialSelection.slice();
        query = '';
        results = [];
        failed = false;
      });
    } else {
      controller?.abort();
    }
  });

  function onInput(): void {
    clearTimeout(timer);
    timer = setTimeout(() => void load(), 300);
  }

  const isSelected = (icon: IconResult): boolean => selected.some((item) => item.id === icon.id);

  function choose(icon: IconResult): void {
    if (!multiple) {
      onSelect([icon]);
      open = false;

      return;
    }

    selected = isSelected(icon) ? selected.filter((item) => item.id !== icon.id) : [...selected, icon];
  }

  function confirm(): void {
    onSelect(selected);
    open = false;
  }
</script>

<FormModal bind:open {title} subtitle={t('ui.ikon_dari_iconify')} size="lg">
  {#snippet children()}
    <input
      type="search"
      class="form-control mb-3"
      placeholder={t('ui.cari_ikon')}
      aria-label={t('ui.cari_ikon')}
      bind:value={query}
      oninput={onInput}
    />

    {#if failed}
      <p class="text-danger small mb-0">{t('ui.ikon_gagal_dimuat')}</p>
    {:else if query.trim() === ''}
      <p class="text-muted small mb-0">{t('ui.ketik_nama_teknologi_untuk_mencari_ikon')}</p>
    {:else if !loading && results.length === 0}
      <p class="text-muted small mb-0">{t('ui.ikon_tidak_ditemukan')}</p>
    {/if}

    <div class="icon-grid" aria-busy={loading}>
      {#each results as icon (icon.id)}
        <button
          type="button"
          class="icon-grid-item"
          class:is-selected={isSelected(icon)}
          title={icon.id}
          aria-pressed={multiple ? isSelected(icon) : undefined}
          onclick={() => choose(icon)}
        >
          {#if multiple}
            <span class="icon-check" aria-hidden="true">
              {#if isSelected(icon)}<Check size={12} strokeWidth={3} />{/if}
            </span>
          {/if}
          <img src={icon.url} alt="" width="32" height="32" loading="lazy" />
          <span class="text-truncate">{icon.name}</span>
        </button>
      {/each}
    </div>

    {#if multiple}
      <div class="d-flex justify-content-end mt-3">
        <button type="button" class="btn btn-primary" onclick={confirm}>
          {t('ui.pilih_n_berkas', { count: selected.length })}
        </button>
      </div>
    {/if}
  {/snippet}
</FormModal>

<style>
  .icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(6rem, 1fr));
    gap: 0.5rem;
    max-height: 55vh;
    overflow-y: auto;
  }

  .icon-grid-item {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    align-items: center;
    padding: 0.625rem 0.375rem;
    font-size: 0.75rem;
    color: inherit;
    background: transparent;
    border: 1px solid var(--neo-border-color);
    border-radius: 0.5rem;
    transition:
      border-color 0.15s,
      background-color 0.15s,
      box-shadow 0.15s;
  }

  .icon-grid-item span {
    max-width: 100%;
  }

  .icon-grid-item:hover {
    border-color: var(--neo-primary);
  }

  .icon-grid-item:focus-visible {
    outline: none;
    border-color: var(--neo-primary);
    box-shadow: 0 0 0 0.2rem rgba(var(--neo-primary-rgb), 0.25);
  }

  .icon-grid-item.is-selected {
    border-color: var(--neo-primary);
    background: rgba(var(--neo-primary-rgb), 0.1);
    box-shadow: 0 0 0 1px var(--neo-primary);
  }

  .icon-check {
    position: absolute;
    top: 0.375rem;
    right: 0.375rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1rem;
    height: 1rem;
    color: #fff;
    background: var(--neo-body-bg);
    border: 1px solid var(--neo-border-color);
    border-radius: 0.25rem;
  }

  .is-selected .icon-check {
    background: var(--neo-primary);
    border-color: var(--neo-primary);
  }
</style>

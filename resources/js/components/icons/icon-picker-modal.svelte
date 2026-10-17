<script lang="ts">
  import { t } from '@/lib/i18n';
  import { untrack } from 'svelte';
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
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    align-items: center;
    padding: 0.625rem 0.375rem;
    font-size: 0.75rem;
    color: inherit;
    background: transparent;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
  }

  .icon-grid-item span {
    max-width: 100%;
  }

  .icon-grid-item:hover,
  .icon-grid-item.is-selected {
    border-color: var(--bs-primary);
    background: color-mix(in srgb, var(--bs-primary) 10%, transparent);
  }
</style>

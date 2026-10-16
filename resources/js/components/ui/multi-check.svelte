<script lang="ts">
  import { t } from '@/lib/i18n';
  /**
   * Checkbox-list multi-select for pivot relations, posted as `name[]`.
   * The search box only hides rows (never unmounts them), so checked items
   * stay submitted while filtered out.
   */
  let {
    name,
    options,
    selected = [],
    searchable = true,
    onCreate,
  }: {
    name: string;
    options: { value: number; label: string }[];
    selected?: number[];
    searchable?: boolean;
    /** When set, a missing search term can be added as a new option. */
    onCreate?: (name: string) => Promise<void>;
  } = $props();

  let query = $state('');
  let creating = $state(false);

  const needle = $derived(query.trim().toLowerCase());
  const hasMatch = $derived(
    options.some((option) => option.label.toLowerCase().includes(needle)),
  );
  const canCreate = $derived(
    !!onCreate &&
      needle !== '' &&
      !options.some((option) => option.label.toLowerCase() === needle),
  );

  async function create(): Promise<void> {
    if (!onCreate || !canCreate || creating) return;

    creating = true;

    try {
      await onCreate(query.trim());
      query = '';
    } catch {
      // The caller already reported the failure.
    } finally {
      creating = false;
    }
  }
</script>

{#if searchable && (options.length > 0 || onCreate)}
  <input
    type="search"
    class="form-control form-control-sm mb-2"
    placeholder={onCreate ? t('ui.cari_atau_tambah') : t('ui.cari')}
    aria-label={t('ui.cari_opsi')}
    bind:value={query}
    onkeydown={(event) => {
      if (event.key !== 'Enter') return;

      event.preventDefault();
      void create();
    }}
  />
{/if}

{#if canCreate}
  <button
    type="button"
    class="btn btn-sm btn-outline-primary mb-2 text-start text-truncate"
    disabled={creating}
    onclick={create}
  >
    + Tambah “{query.trim()}”
  </button>
{/if}

<div class="multi-check">
  {#if options.length === 0}
    <p class="text-muted small mb-0">
      {onCreate
        ? t('ui.belum_ada_data_ketik_nama_untuk')
        : t('admin-visitor-analytics.belum_ada_data')}
    </p>
  {:else}
    {#if !hasMatch}
      <p class="text-muted small mb-0">{t('ui.tidak_ada_hasil')}</p>
    {/if}
    {#each options as option (option.value)}
      <div
        class="form-check"
        hidden={!option.label.toLowerCase().includes(needle)}
      >
        <input
          type="checkbox"
          class="form-check-input"
          id={`${name}-${option.value}`}
          name={`${name}[]`}
          value={option.value}
          checked={selected.includes(option.value)}
        />
        <label
          class="form-check-label"
          for={`${name}-${option.value}`}
          title={option.label}
        >
          {option.label}
        </label>
      </div>
    {/each}
  {/if}
</div>

<style>
  .multi-check {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    max-height: 12rem;
    overflow-y: auto;
    padding: 0.75rem;
    border: 1px solid var(--neo-border-color);
    border-radius: 0.5rem;
  }

  .form-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
    margin: 0;
    padding-left: 0;
  }

  .form-check[hidden] {
    display: none;
  }

  .form-check-input {
    flex-shrink: 0;
    float: none;
    margin: 0;
  }

  .form-check-label {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
</style>

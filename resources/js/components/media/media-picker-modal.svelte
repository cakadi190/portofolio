<script lang="ts">
  import { t } from '@/lib/i18n';
  import { untrack } from 'svelte';
  import FileText from '@lucide/svelte/icons/file-text';
  import Upload from '@lucide/svelte/icons/upload';
  import FormModal from '@/components/form-modal.svelte';
  import MediaImage from '@/components/media/media-image.svelte';
  import PaginationNav from '@/components/pagination-nav.svelte';
  import { formatBytes, uploadMedia } from '@/lib/media';
  import { toast } from '@/lib/toast';
  import { browse } from '@/wayfinder/routes/admin/media';
  import type { Paginated } from '@/types/pagination';
  import type { MediaAccept, MediaItem, MediaSelectionMode } from '@/types/media';

  /**
   * WordPress-style media picker: browse (server-side pages) and search the
   * library, upload new files (button or drag & drop), tick the items to use
   * and confirm.
   *
   * `mode="single"` (Choose Single) keeps exactly one item and calls
   * `onSelect(media)`; `mode="multiple"` (Choose Multi) keeps any number,
   * across pages, and calls `onSelectMultiple(items)` in the order they were
   * picked. Cancelling or closing never calls either callback.
   */
  let {
    open = $bindable(false),
    accept = 'all',
    title = t('ui.pilih_media'),
    mode = 'single',
    initialSelection = [],
    onSelect,
    onSelectMultiple,
  }: {
    open?: boolean;
    accept?: MediaAccept;
    title?: string;
    mode?: MediaSelectionMode;
    initialSelection?: MediaItem[];
    onSelect?: (media: MediaItem) => void;
    onSelectMultiple?: (items: MediaItem[]) => void;
  } = $props();

  let selected = $state<MediaItem[]>([]);

  let items = $state<MediaItem[]>([]);
  let meta = $state<{ total: number; from: number | null; to: number | null }>({
    total: 0,
    from: null,
    to: null,
  });
  let page = $state(1);
  let lastPage = $state(1);
  let search = $state('');
  let loading = $state(false);
  let uploading = $state(false);
  let error = $state<string | null>(null);
  let dragOver = $state(false);
  let fileInput = $state<HTMLInputElement>();
  let searchTimer: ReturnType<typeof setTimeout> | undefined;
  let controller: AbortController | undefined;

  const isMultiple = $derived(mode === 'multiple');
  const confirmLabel = $derived(t('ui.pilih_n_berkas', { count: selected.length }));

  /** Loads the current `page`/`search`; a newer call aborts the one in flight. */
  async function load(): Promise<void> {
    controller?.abort();
    const current = new AbortController();
    controller = current;
    loading = true;
    error = null;

    try {
      const response = await fetch(
        browse({
          query: {
            page,
            search: search || undefined,
            type: accept === 'image' ? 'image' : undefined,
          },
        }).url,
        {
          headers: { Accept: 'application/json' },
          credentials: 'same-origin',
          signal: current.signal,
        },
      );

      if (!response.ok) {
        throw new Error();
      }

      const payload = (await response.json()) as Paginated<MediaItem>;

      if (current.signal.aborted) {
        return;
      }

      if (payload.data.length === 0 && page > 1 && payload.last_page >= 1) {
        page = Math.min(page - 1, payload.last_page);
        await load();

        return;
      }

      items = payload.data;
      lastPage = payload.last_page;
      meta = { total: payload.total ?? payload.data.length, from: payload.from ?? null, to: payload.to ?? null };
    } catch (exception) {
      if (current.signal.aborted || (exception instanceof DOMException && exception.name === 'AbortError')) {
        return;
      }

      error = t('ui.pustaka_media_gagal_dimuat');
      toast.error(error);
    } finally {
      if (controller === current) {
        loading = false;
      }
    }
  }

  $effect(() => {
    if (open) {
      untrack(() => {
        selected = (isMultiple ? initialSelection : initialSelection.slice(0, 1)).slice();
        page = 1;
        error = null;
        void load();
      });
    } else {
      controller?.abort();
    }
  });

  function onSearch(): void {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      page = 1;
      void load();
    }, 300);
  }

  function goTo(next: number): void {
    page = next;
    void load();
  }

  async function upload(files: FileList | null): Promise<void> {
    if (!files?.length) {
      return;
    }

    uploading = true;
    error = null;

    try {
      for (const file of Array.from(files)) {
        if (accept === 'image' && !file.type.startsWith('image/')) {
          throw new Error(t('ui.hanya_berkas_gambar_yang_diperbolehkan_di'));
        }

        await uploadMedia(file);
      }

      page = 1;
      await load();
    } catch (exception) {
      error = exception instanceof Error ? exception.message : t('ui.unggahan_gagal');
    } finally {
      uploading = false;

      if (fileInput) {
        fileInput.value = '';
      }
    }
  }

  const isSelected = (media: MediaItem): boolean => selected.some((item) => item.id === media.id);

  /** Ticks or unticks one item; single mode replaces the previous choice. */
  function toggle(media: MediaItem, checked: boolean): void {
    if (!isMultiple) {
      selected = checked ? [media] : [];

      return;
    }

    selected = checked
      ? isSelected(media)
        ? selected
        : [...selected, media]
      : selected.filter((item) => item.id !== media.id);
  }

  function confirmSelection(): void {
    if (selected.length === 0) {
      return;
    }

    if (isMultiple) {
      onSelectMultiple?.([...selected]);
    } else {
      onSelect?.(selected[0]);
    }

    open = false;
  }
</script>

<FormModal
  bind:open
  {title}
  subtitle={`${isMultiple ? t('ui.pilih_banyak_media') : t('ui.pilih_satu_media')} · ${t('ui.pilih_dari_pustaka_atau_unggah_berkas')}`}
  size="xl"
>
  {#snippet children()}
    <div
      class="media-picker"
      class:is-dragover={dragOver}
      role="presentation"
      ondragover={(event) => {
        event.preventDefault();
        dragOver = true;
      }}
      ondragleave={() => (dragOver = false)}
      ondrop={(event) => {
        event.preventDefault();
        dragOver = false;
        void upload(event.dataTransfer?.files ?? null);
      }}
    >
      <div class="d-flex flex-wrap gap-2 mb-3">
        <input
          type="search"
          aria-label={t('ui.cari_media')}
          class="form-control w-auto flex-grow-1"
          placeholder={t('ui.cari_media_2')}
          bind:value={search}
          oninput={onSearch}
        />
        <button
          type="button"
          class="btn btn-primary d-inline-flex align-items-center gap-2"
          disabled={uploading}
          onclick={() => fileInput?.click()}
        >
          <Upload size={16} />
          {uploading ? t('ui.mengunggah') : t('ui.unggah_berkas')}
        </button>
        <input
          bind:this={fileInput}
          type="file"
          class="visually-hidden"
          multiple
          accept={accept === 'image' ? 'image/*' : 'image/*,application/pdf'}
          onchange={(event) => void upload((event.target as HTMLInputElement).files)}
        />
      </div>

      {#if error}
        <div class="alert alert-danger py-2 small d-flex justify-content-between align-items-center" role="alert">
          <span>{error}</span>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick={() => void load()}>
            {t('ui.coba_lagi')}
          </button>
        </div>
      {/if}

      {#if loading && items.length === 0}
        <p class="text-muted text-center py-5 mb-0" role="status">{t('ui.memuat')}</p>
      {:else if items.length === 0 && !error}
        <p class="text-muted text-center py-5 mb-0">
          {search ? t('ui.tidak_ada_media_ditemukan') : t('ui.belum_ada_media_seret_berkas_ke')}
        </p>
      {:else}
        <div class="media-picker-grid" class:is-loading={loading} aria-busy={loading}>
          {#each items as media (media.id)}
            <label class="media-picker-item" class:is-selected={isSelected(media)} title={media.name}>
              <input
                type="checkbox"
                class="form-check-input media-picker-check"
                aria-label={t('ui.pilih_item_media', { name: media.name })}
                checked={isSelected(media)}
                onchange={(event) => toggle(media, (event.currentTarget as HTMLInputElement).checked)}
              />
              {#if media.is_image}
                <MediaImage {media} sizes="(min-width: 768px) 14vw, (min-width: 576px) 33vw, 50vw" />
              {:else}
                <span class="media-picker-doc"><FileText size={32} /></span>
              {/if}
              <span class="media-picker-name">{media.name}</span>
              <span class="media-picker-meta">{formatBytes(media.size)}</span>
            </label>
          {/each}
        </div>

        <PaginationNav
          currentPage={page}
          {lastPage}
          total={meta.total}
          from={meta.from}
          to={meta.to}
          disabled={loading}
          onNavigate={goTo}
        />
      {/if}

      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
        <span class="small text-muted" aria-live="polite">
          {selected.length > 0
            ? t('ui.n_berkas_dipilih', { count: selected.length })
            : t('ui.belum_ada_media_dipilih_picker')}
        </span>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary" onclick={() => (open = false)}>
            {t('ui.batal_pilih')}
          </button>
          <button
            type="button"
            class="btn btn-primary"
            disabled={selected.length === 0}
            onclick={confirmSelection}
          >
            {confirmLabel}
          </button>
        </div>
      </div>
    </div>
  {/snippet}
</FormModal>

<style>
  /* Sits above the form modal it is usually opened from. */
  :global(.modal:has(.media-picker)) {
    z-index: 1065;
  }

  .media-picker {
    min-height: 16rem;
    border-radius: 0.5rem;
    border: 2px dashed transparent;
  }

  .media-picker.is-dragover {
    border-color: var(--neo-primary);
    background-color: rgba(var(--neo-primary-rgb), 0.05);
  }

  .media-picker-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
  }

  @media (min-width: 576px) {
    .media-picker-grid {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }
  }

  @media (min-width: 768px) {
    .media-picker-grid {
      grid-template-columns: repeat(7, minmax(0, 1fr));
    }
  }

  .media-picker-item {
    position: relative;
    cursor: pointer;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 0.5rem;
    text-align: left;
    background: none;
    border: 1px solid var(--neo-border-color);
    border-radius: 0.5rem;
  }

  .media-picker-item.is-selected {
    border-color: var(--neo-primary);
    box-shadow: 0 0 0 2px var(--neo-primary);
  }

  .media-picker-grid.is-loading {
    opacity: 0.6;
    transition: opacity 0.15s;
  }

  .media-picker-check {
    position: absolute;
    top: 0.9rem;
    left: 0.9rem;
    z-index: 1;
    margin: 0;
  }

  .media-picker-item:hover,
  .media-picker-item:focus-within {
    border-color: var(--neo-primary);
  }

  .media-picker-item :global(img),
  .media-picker-doc {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: auto;
    aspect-ratio: 1;
    object-fit: cover;
    border-radius: 0.375rem;
    background-color: var(--neo-tertiary-bg);
  }

  .media-picker-name {
    overflow: hidden;
    font-size: 0.8rem;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .media-picker-meta {
    font-size: 0.7rem;
    color: var(--neo-secondary-color);
  }
</style>

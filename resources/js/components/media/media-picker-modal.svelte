<script lang="ts">
  import { t } from '@/lib/i18n';
  import { untrack } from 'svelte';
  import FileText from '@lucide/svelte/icons/file-text';
  import Upload from '@lucide/svelte/icons/upload';
  import FormModal from '@/components/form-modal.svelte';
  import { formatBytes, uploadMedia } from '@/lib/media';
  import { toast } from '@/lib/toast';
  import { browse } from '@/wayfinder/routes/admin/media';
  import type { Paginated } from '@/types/pagination';
  import type { MediaAccept, MediaItem } from '@/types/media';

  /**
   * WordPress-style media picker: browse and search the library, upload new
   * files (button or drag & drop), then pick one. Calls `onSelect` and closes.
   */
  let {
    open = $bindable(false),
    accept = 'all',
    title = t('ui.pilih_media'),
    multiple = false,
    onSelect,
  }: {
    open?: boolean;
    accept?: MediaAccept;
    title?: string;
    multiple?: boolean;
    onSelect: (media: MediaItem) => void;
  } = $props();

  let selected = $state<MediaItem[]>([]);

  let items = $state<MediaItem[]>([]);
  let page = $state(1);
  let lastPage = $state(1);
  let search = $state('');
  let loading = $state(false);
  let uploading = $state(false);
  let error = $state<string | null>(null);
  let dragOver = $state(false);
  let fileInput = $state<HTMLInputElement>();
  let searchTimer: ReturnType<typeof setTimeout> | undefined;

  async function load(): Promise<void> {
    loading = true;

    try {
      const response = await fetch(
        browse({
          query: {
            page,
            search: search || undefined,
            type: accept === 'image' ? 'image' : undefined,
          },
        }).url,
        { headers: { Accept: 'application/json' }, credentials: 'same-origin' },
      );

      if (!response.ok) {
        throw new Error();
      }

      const payload = (await response.json()) as Paginated<MediaItem>;
      items = page > 1 ? [...items, ...payload.data] : payload.data;
      lastPage = payload.last_page;
    } catch {
      error = t('ui.pustaka_media_gagal_dimuat');
      toast.error(error);
    } finally {
      loading = false;
    }
  }

  $effect(() => {
    if (open) {
      untrack(() => {
        selected = [];
        page = 1;
        error = null;
        void load();
      });
    }
  });

  function onSearch(): void {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      page = 1;
      void load();
    }, 300);
  }

  /** Loads the next page when the sentinel below the grid scrolls into view. */
  function loadMore(): void {
    if (loading || page >= lastPage) {
      return;
    }

    page += 1;
    void load();
  }

  function observeEnd(node: HTMLElement) {
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
          loadMore();
        }
      },
      { rootMargin: '200px' },
    );
    observer.observe(node);

    return { destroy: () => observer.disconnect() };
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

  function choose(media: MediaItem): void {
    if (multiple) {
      selected = selected.some((item) => item.id === media.id)
        ? selected.filter((item) => item.id !== media.id)
        : [...selected, media];

      return;
    }

    onSelect(media);
    open = false;
  }

  function confirmSelection(): void {
    selected.forEach(onSelect);
    open = false;
  }
</script>

<FormModal bind:open {title} subtitle={t('ui.pilih_dari_pustaka_atau_unggah_berkas')} size="xl">
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
        <div class="alert alert-danger py-2 small">{error}</div>
      {/if}

      {#if loading && items.length === 0}
        <p class="text-muted text-center py-5 mb-0">{t('ui.memuat')}</p>
      {:else if items.length === 0}
        <p class="text-muted text-center py-5 mb-0">
          {t('ui.belum_ada_media_seret_berkas_ke')}
        </p>
      {:else}
        <div class="media-picker-grid">
          {#each items as media (media.id)}
            <button
              type="button"
              class="media-picker-item"
              class:is-selected={selected.some((item) => item.id === media.id)}
              title={media.name}
              onclick={() => choose(media)}
            >
              {#if media.is_image}
                <img src={media.url} alt={media.alt ?? media.name} loading="lazy" />
              {:else}
                <span class="media-picker-doc"><FileText size={32} /></span>
              {/if}
              <span class="media-picker-name">{media.name}</span>
              <span class="media-picker-meta">{formatBytes(media.size)}</span>
            </button>
          {/each}
        </div>

        {#if page < lastPage}
          <div
            class="d-flex justify-content-center align-items-center gap-2 py-3 small text-muted"
            role="status"
            use:observeEnd
          >
            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
            {t('ui.memuat_lebih_banyak')}
          </div>
        {/if}
      {/if}

      {#if multiple}
        <div class="d-flex justify-content-end mt-3">
          <button
            type="button"
            class="btn btn-primary"
            disabled={selected.length === 0}
            onclick={confirmSelection}
          >
            Tambahkan {selected.length} berkas
          </button>
        </div>
      {/if}
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

  .media-picker-item:hover,
  .media-picker-item:focus-visible {
    border-color: var(--neo-primary);
  }

  .media-picker-item img,
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

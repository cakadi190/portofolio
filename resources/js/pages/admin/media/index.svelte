<script lang="ts">
  import { t } from '@/lib/i18n';
  import Select from '@/components/ui/select.svelte';
  import Copy from '@lucide/svelte/icons/copy';
  import File from '@lucide/svelte/icons/file';
  import FileArchive from '@lucide/svelte/icons/file-archive';
  import FileImage from '@lucide/svelte/icons/file-image';
  import FileSpreadsheet from '@lucide/svelte/icons/file-spreadsheet';
  import FileText from '@lucide/svelte/icons/file-text';
  import LayoutGrid from '@lucide/svelte/icons/layout-grid';
  import List from '@lucide/svelte/icons/list';
  import Pencil from '@lucide/svelte/icons/pencil';
  import Trash2 from '@lucide/svelte/icons/trash-2';
  import Upload from '@lucide/svelte/icons/upload';
  import AppHead from '@/components/app-head.svelte';
  import ModalConfirmation from '@/components/modal-confirmation.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import InfiniteFeed from '@/components/infinite-feed.svelte';
  import { Field } from '@/components/ui/field';
  import { formatBytes, uploadMedia } from '@/lib/media';
  import { toast } from '@/lib/toast';
  import { formatDate } from '@/lib/utils';
  import { Form, router } from '@inertiajs/svelte';
  import { destroy, index, update } from '@/wayfinder/routes/admin/media';
  import type { MediaItem } from '@/types/media';
  import type { InfiniteList } from '@/types/pagination';

  let {
    media,
    filters,
  }: {
    media: InfiniteList<MediaItem>;
    filters: { search: string; type: 'all' | 'image' | 'document' };
  } = $props();

  let search = $state('');
  let type = $state<'all' | 'image' | 'document'>('all');
  let uploading = $state(false);
  let error = $state<string | null>(null);
  let dragOver = $state(false);
  let fileInput = $state<HTMLInputElement>();
  let editOpen = $state(false);
  let view = $state<'grid' | 'list'>('grid');

  try {
    view = localStorage.getItem('media-view') === 'list' ? 'list' : 'grid';
  } catch {
    // storage unavailable
  }

  function setView(next: 'grid' | 'list'): void {
    view = next;

    try {
      localStorage.setItem('media-view', next);
    } catch {
      // storage unavailable
    }
  }

  function fileIcon(item: MediaItem) {
    if (item.is_image) {
      return FileImage;
    }

    if (item.mime_type === 'application/pdf') {
      return FileText;
    }

    if (/sheet|excel|csv/.test(item.mime_type)) {
      return FileSpreadsheet;
    }

    if (/zip|rar|compressed/.test(item.mime_type)) {
      return FileArchive;
    }

    return File;
  }

  function fileLabel(item: MediaItem): string {
    return item.path.split('.').pop()?.toUpperCase() ?? 'FILE';
  }

  let deleteOpen = $state(false);
  let deleting = $state<MediaItem | null>(null);
  let deleteProcessing = $state(false);

  function confirmDelete(item: MediaItem): void {
    deleting = item;
    deleteOpen = true;
  }

  function performDelete(): void {
    if (!deleting) {
      return;
    }

    deleteProcessing = true;
    router.delete(destroy(deleting.id).url, {
      preserveScroll: true,
      onFinish: () => {
        deleteProcessing = false;
      },
    });
  }
  let editing = $state<MediaItem | null>(null);
  let copiedId = $state<number | null>(null);
  let timer: ReturnType<typeof setTimeout> | undefined;

  $effect(() => {
    search = filters.search;
    type = filters.type;
  });

  function applyFilters(): void {
    clearTimeout(timer);
    timer = setTimeout(() => {
      router.get(
        index().url,
        {
          search: search || undefined,
          type: type === 'all' ? undefined : type,
        },
        { preserveState: true, replace: true },
      );
    }, 300);
  }

  async function upload(files: FileList | null): Promise<void> {
    if (!files?.length) {
      return;
    }

    uploading = true;
    error = null;

    try {
      for (const file of Array.from(files)) {
        await uploadMedia(file);
      }

      router.reload({ only: ['media'], reset: ['media'] });
    } catch (exception) {
      error =
        exception instanceof Error ? exception.message : t('ui.unggahan_gagal');
    } finally {
      uploading = false;

      if (fileInput) {
        fileInput.value = '';
      }
    }
  }

  function openEdit(item: MediaItem): void {
    editing = item;
    editOpen = true;
  }

  async function copyUrl(item: MediaItem): Promise<void> {
    try {
      await navigator.clipboard.writeText(
        new URL(item.url ?? '', window.location.origin).href,
      );
    } catch {
      toast.error(t('admin-media.tautan_gagal_disalin'));

      return;
    }

    toast.success(t('admin-media.tautan_berhasil_disalin'));
    copiedId = item.id;
    setTimeout(() => (copiedId = null), 1500);
  }
</script>

<AppHead title={t('admin-media.pustaka_media')} />

<AdminPageHeader
  title={t('admin-media.pustaka_media')}
  subtitle={t('admin-media.kelola_semua_gambar_dan_dokumen_yang')}
/>

<div
  class="media-dropzone card card-body mb-4"
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
  <div class="d-flex flex-wrap align-items-center gap-3">
    <button
      type="button"
      class="btn btn-primary d-inline-flex align-items-center gap-2"
      disabled={uploading}
      onclick={() => fileInput?.click()}
    >
      <Upload size={16} />
      {uploading ? t('ui.mengunggah') : t('ui.unggah_berkas')}
    </button>
    <span class="text-muted small"
      >{t('admin-media.atau_seret_berkas_ke_sini_jpg')}</span
    >
    <input
      bind:this={fileInput}
      type="file"
      class="visually-hidden"
      multiple
      accept="image/*,application/pdf"
      onchange={(event) =>
        void upload((event.target as HTMLInputElement).files)}
    />
  </div>
  {#if error}
    <div class="alert alert-danger py-2 small mt-3 mb-0">{error}</div>
  {/if}
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
  <input
    type="search"
    aria-label={t('admin-media.cari_media')}
    class="form-control w-auto flex-grow-1"
    placeholder={t('admin-media.cari_nama_atau_teks_alternatif')}
    bind:value={search}
    oninput={applyFilters}
  />
  <Select
    inline
    required
    searchable={false}
    ariaLabel={t('admin-media.filter_jenis_media')}
    items={[
      { value: 'all', label: t('admin-media.semua_jenis') },
      { value: 'image', label: t('admin.gambar') },
      { value: 'document', label: t('ui.dokumen') },
    ]}
    value={type}
    onchange={(next) => {
      type = (next as typeof type | null) ?? 'all';
      applyFilters();
    }}
  />
  <div class="btn-group" role="group" aria-label={t('admin-media.tampilan')}>
    <button
      type="button"
      class="btn btn-square btn-outline-secondary"
      class:active={view === 'grid'}
      aria-label={t('admin-media.tampilan_kartu')}
      aria-pressed={view === 'grid'}
      onclick={() => setView('grid')}
    >
      <LayoutGrid size={16} />
    </button>
    <button
      type="button"
      class="btn btn-square btn-outline-secondary"
      class:active={view === 'list'}
      aria-label={t('admin-media.tampilan_tabel')}
      aria-pressed={view === 'list'}
      onclick={() => setView('list')}
    >
      <List size={16} />
    </button>
  </div>
</div>

{#if media.data.length === 0}
  <div class="text-center text-muted py-5">
    <p class="fw-semibold mb-1">{t('admin-media.belum_ada_media')}</p>
    <p class="mb-0">{t('admin-media.unggah_berkas_pertama_anda')}</p>
  </div>
{:else}
  <InfiniteFeed data="media">
  {#if view === 'grid'}
    <div class="media-grid">
      {#each media.data as item (item.id)}
        {@const Icon = fileIcon(item)}
        <div class="card h-100 overflow-hidden">
          <div class="media-thumb-wrap">
            <button
              type="button"
              class="media-thumb"
              title={t('admin-media.ubah_detail')}
              onclick={() => openEdit(item)}
            >
              {#if item.is_image}
                <img
                  src={item.url}
                  alt={item.alt ?? item.name}
                  loading="lazy"
                />
              {:else}
                <Icon size={40} />
              {/if}
            </button>
            <span class="media-type-badge"
              ><Icon size={12} />{fileLabel(item)}</span
            >
            <div class="media-toolbar" role="toolbar" aria-label={t('admin-media.aksi_media')}>
              <button
                type="button"
                class="btn btn-square btn-sm btn-light"
                title={t('admin.ubah')}
                aria-label={t('admin.ubah')}
                onclick={() => openEdit(item)}
              >
                <Pencil size={14} />
              </button>
              <button
                type="button"
                class="btn btn-square btn-sm btn-light"
                title={copiedId === item.id ? t('ui.tersalin') : t('admin-media.salin_url')}
                aria-label={t('admin-media.salin_url')}
                onclick={() => void copyUrl(item)}
              >
                <Copy size={14} />
              </button>
              <button
                type="button"
                class="btn btn-square btn-sm btn-danger"
                title={t('admin-media.hapus')}
                aria-label={t('admin-media.hapus')}
                onclick={() => confirmDelete(item)}
              >
                <Trash2 size={14} />
              </button>
            </div>
          </div>
          <div class="card-body p-2 d-flex flex-column gap-1">
            <div class="small fw-semibold text-truncate" title={item.name}>
              {item.name}
            </div>
            <div class="small text-muted">
              {formatBytes(item.size)}{item.width
                ? ` · ${item.width}×${item.height}`
                : ''}
            </div>
          </div>
        </div>
      {/each}
    </div>
  {:else}
    <div class="card overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th style="width:4.5rem">{t('admin-media.berkas')}</th>
              <th>{t('admin.nama')}</th>
              <th>{t('admin-media.jenis')}</th>
              <th>{t('admin-media.ukuran')}</th>
              <th>{t('admin-media.diunggah')}</th>
              <th class="text-end">{t('admin-media.aksi')}</th>
            </tr>
          </thead>
          <tbody>
            {#each media.data as item (item.id)}
              {@const Icon = fileIcon(item)}
              <tr>
                <td>
                  <div class="media-row-thumb">
                    {#if item.is_image}
                      <img
                        src={item.url}
                        alt={item.alt ?? item.name}
                        loading="lazy"
                      />
                    {:else}
                      <Icon size={22} />
                    {/if}
                  </div>
                </td>
                <td class="fw-semibold text-break">{item.name}</td>
                <td
                  ><span
                    class="badge text-bg-secondary d-inline-flex align-items-center gap-1"
                    ><Icon size={12} />{fileLabel(item)}</span
                  ></td
                >
                <td class="text-nowrap"
                  >{formatBytes(item.size)}{item.width
                    ? ` · ${item.width}×${item.height}`
                    : ''}</td
                >
                <td class="text-nowrap">{formatDate(item.created_at)}</td>
                <td class="text-end text-nowrap">
                  <div class="btn-group btn-group-sm">
                    <button
                      type="button"
                      class="btn btn-square btn-outline-secondary"
                      title={t('admin.ubah')}
                      aria-label={t('admin.ubah')}
                      onclick={() => openEdit(item)}
                      ><Pencil size={14} /></button
                    >
                    <button
                      type="button"
                      class="btn btn-square btn-outline-secondary"
                      title={t('admin-media.salin_url')}
                      aria-label={t('admin-media.salin_url')}
                      onclick={() => void copyUrl(item)}
                      ><Copy size={14} /></button
                    >
                    <button
                      type="button"
                      class="btn btn-square btn-outline-danger"
                      title={t('admin-media.hapus')}
                      aria-label={t('admin-media.hapus')}
                      onclick={() => confirmDelete(item)}
                      ><Trash2 size={14} /></button
                    >
                  </div>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </div>
  {/if}
  </InfiniteFeed>
{/if}

<ModalConfirmation
  bind:open={deleteOpen}
  title={`Hapus media "${deleting?.name ?? ''}"?`}
  description={t('admin-media.media_yang_masih_dipakai_tidak_dapat')}
  processing={deleteProcessing}
  confirmLabel={t('admin-media.hapus')}
  actions={{ confirm: performDelete }}
/>

<FormModal
  bind:open={editOpen}
  title={t('admin-media.detail_media')}
  subtitle={editing?.path ?? ''}
>
  {#snippet children()}
    {#if editing}
      <Form
        {...update.form(editing.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          {#if editing?.is_image}
            <img
              src={editing.url}
              alt={editing.alt ?? editing.name}
              class="img-fluid rounded"
            />
          {/if}

          <Field.Group>
            <Field.Label for="edit-name">{t('admin.nama')}</Field.Label>
            <Field.Input
              placeholder={t('admin.masukkan_nama')}
              id="edit-name"
              name="name"
              required
              value={editing?.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-alt">{t('admin-media.teks_alternatif')}</Field.Label>
            <Field.Input
              placeholder={t('admin-media.masukkan_teks_alternatif')}
              id="edit-alt"
              name="alt"
              value={editing?.alt}
              invalid={!!errors.alt}
            />
            <Field.Feedback message={errors.alt} />
          </Field.Group>

          <div class="d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary"
              onclick={() => (editOpen = false)}
            >
              {t('admin.batal')}
            </button>
            <button type="submit" class="btn btn-primary" disabled={processing}
              >{t('admin.simpan')}</button
            >
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>

<style>
  .media-dropzone {
    border-style: dashed;
  }

  .media-dropzone.is-dragover {
    border-color: var(--neo-primary);
    background-color: rgba(var(--neo-primary-rgb), 0.05);
  }

  .media-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }

  @media (min-width: 640px) {
    .media-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  @media (min-width: 1024px) {
    .media-grid {
      grid-template-columns: repeat(6, 1fr);
    }
  }

  .media-thumb-wrap {
    position: relative;
    overflow: hidden;
  }

  .media-toolbar {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: flex;
    gap: 0.25rem;
    opacity: 0;
    transform: translateY(-0.5rem);
    pointer-events: none;
    transition:
      opacity 0.15s ease,
      transform 0.15s ease;
  }

  .media-thumb-wrap:hover .media-toolbar,
  .media-thumb-wrap:focus-within .media-toolbar {
    opacity: 1;
    transform: none;
    pointer-events: auto;
  }

  @media (hover: none) {
    .media-toolbar {
      opacity: 1;
      transform: none;
      pointer-events: auto;
    }
  }

  .media-type-badge {
    position: absolute;
    left: 0.5rem;
    bottom: 0.5rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.375rem;
    border-radius: 0.375rem;
    font-size: 0.6875rem;
    font-weight: 600;
    background: rgba(0, 0, 0, 0.6);
    color: #fff;
  }

  .media-row-thumb {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    overflow: hidden;
    border-radius: 0.375rem;
    background-color: var(--neo-tertiary-bg);
  }

  .media-row-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .media-thumb {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    aspect-ratio: 4 / 3;
    padding: 0;
    border: none;
    background-color: var(--neo-tertiary-bg);
  }

  .media-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
</style>

<script lang="ts">
  import FileText from '@lucide/svelte/icons/file-text';
  import Upload from '@lucide/svelte/icons/upload';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Field } from '@/components/ui/field';
  import { formatBytes, uploadMedia } from '@/lib/media';
  import { Form, router } from '@inertiajs/svelte';
  import { destroy, index, update } from '@/wayfinder/routes/admin/media';
  import type { MediaItem } from '@/types/media';
  import type { Paginated } from '@/types/pagination';

  let {
    media,
    filters,
  }: {
    media: Paginated<MediaItem>;
    filters: { search: string; type: 'all' | 'image' | 'document' };
  } = $props();

  let search = $state('');
  let type = $state<'all' | 'image' | 'document'>('all');
  let uploading = $state(false);
  let error = $state<string | null>(null);
  let dragOver = $state(false);
  let fileInput = $state<HTMLInputElement>();
  let editOpen = $state(false);
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
        { search: search || undefined, type: type === 'all' ? undefined : type },
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

      router.reload({ only: ['media'] });
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Unggahan gagal.';
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
    await navigator.clipboard.writeText(new URL(item.url ?? '', window.location.origin).href);
    copiedId = item.id;
    setTimeout(() => (copiedId = null), 1500);
  }
</script>

<AppHead title="Pustaka Media" />

<AdminPageHeader
  title="Pustaka Media"
  subtitle="Kelola semua gambar dan dokumen yang diunggah, dipakai ulang di seluruh formulir dan editor."
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
      {uploading ? 'Mengunggah…' : 'Unggah Berkas'}
    </button>
    <span class="text-muted small">Atau seret berkas ke sini (JPG, PNG, WebP, GIF, PDF — maks. 10 MB).</span>
    <input
      bind:this={fileInput}
      type="file"
      class="visually-hidden"
      multiple
      accept="image/*,application/pdf"
      onchange={(event) => void upload((event.target as HTMLInputElement).files)}
    />
  </div>
  {#if error}
    <div class="alert alert-danger py-2 small mt-3 mb-0">{error}</div>
  {/if}
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
  <input
    type="search"
    class="form-control w-auto flex-grow-1"
    placeholder="Cari nama atau teks alternatif…"
    bind:value={search}
    oninput={applyFilters}
  />
  <select class="form-select w-auto" bind:value={type} onchange={applyFilters}>
    <option value="all">Semua jenis</option>
    <option value="image">Gambar</option>
    <option value="document">Dokumen</option>
  </select>
</div>

{#if media.data.length === 0}
  <div class="text-center text-muted py-5">
    <p class="fw-semibold mb-1">Belum ada media</p>
    <p class="mb-0">Unggah berkas pertama Anda.</p>
  </div>
{:else}
  <div class="media-grid">
    {#each media.data as item (item.id)}
      <div class="card h-100">
        <button
          type="button"
          class="media-thumb"
          title="Ubah detail"
          onclick={() => openEdit(item)}
        >
          {#if item.is_image}
            <img src={item.url} alt={item.alt ?? item.name} loading="lazy" />
          {:else}
            <FileText size={40} />
          {/if}
        </button>
        <div class="card-body p-2 d-flex flex-column gap-1">
          <div class="small fw-semibold text-truncate" title={item.name}>{item.name}</div>
          <div class="small text-muted">
            {formatBytes(item.size)}{item.width ? ` · ${item.width}×${item.height}` : ''}
          </div>
          <div class="d-flex flex-wrap gap-1 mt-1">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick={() => openEdit(item)}>
              Ubah
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick={() => void copyUrl(item)}>
              {copiedId === item.id ? 'Tersalin' : 'Salin URL'}
            </button>
            <AdminDeleteButton
              href={destroy(item.id).url}
              label={`Hapus media "${item.name}"?`}
              description="Media yang masih dipakai tidak dapat dihapus."
            />
          </div>
        </div>
      </div>
    {/each}
  </div>

  <div class="mt-4">
    <SimplePaginator
      currentPage={media.current_page}
      lastPage={media.last_page}
      prevPageUrl={media.prev_page_url}
      nextPageUrl={media.next_page_url}
    />
  </div>
{/if}

<FormModal bind:open={editOpen} title="Detail Media" subtitle={editing?.path ?? ''}>
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
            <img src={editing.url} alt={editing.alt ?? editing.name} class="img-fluid rounded" />
          {/if}

          <Field.Group>
            <Field.Label for="edit-name">Nama</Field.Label>
            <Field.Input
              id="edit-name"
              name="name"
              required
              value={editing?.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-alt">Teks Alternatif</Field.Label>
            <Field.Input id="edit-alt" name="alt" value={editing?.alt} invalid={!!errors.alt} />
            <Field.Feedback message={errors.alt} />
          </Field.Group>

          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" onclick={() => (editOpen = false)}>
              Batal
            </button>
            <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
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
    grid-template-columns: repeat(auto-fill, minmax(12rem, 1fr));
    gap: 1rem;
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

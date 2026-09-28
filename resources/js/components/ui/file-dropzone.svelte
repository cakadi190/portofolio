<script lang="ts">
  import FileText from '@lucide/svelte/icons/file-text';
  import ImagePlus from '@lucide/svelte/icons/image-plus';
  import X from '@lucide/svelte/icons/x';
  import ImageLightbox from './image-lightbox.svelte';
  import { cn, storageUrl } from '@/lib/utils';

  /**
   * GDrive-style image dropzone: drag & drop or click to browse, a thumbnail
   * preview, and a lightbox on click. Adapted from batamtix's dropzone +
   * lightbox widgets for this project's Svelte/Bootstrap stack.
   *
   * Renders a real `<input type="file" {name}>` so Inertia's `<Form>`
   * collects it via native `FormData` like any other field.
   */
  let {
    name,
    accept = 'image/*',
    maxSizeMb = 4,
    existingUrl = null,
    invalid = false,
    required = false,
    class: className = '',
  }: {
    name: string;
    accept?: string;
    maxSizeMb?: number;
    existingUrl?: string | null;
    invalid?: boolean;
    required?: boolean;
    class?: string;
  } = $props();

  let inputEl = $state<HTMLInputElement>();
  let dragOver = $state(false);
  let previewUrl = $state<string | null>(null);
  let fileName = $state<string | null>(null);
  let error = $state<string | null>(null);
  let lightboxOpen = $state(false);
  let previewIsImage = $state(true);

  const resolvedExistingUrl = $derived(storageUrl(existingUrl));
  const displayUrl = $derived(previewUrl ?? resolvedExistingUrl);
  const displayIsImage = $derived(
    previewUrl ? previewIsImage : !/\.pdf($|\?)/i.test(resolvedExistingUrl ?? ''),
  );

  function applyFiles(files: FileList | null): void {
    error = null;
    const file = files?.[0];

    if (!file) {
      return;
    }

    if (file.size > maxSizeMb * 1024 * 1024) {
      error = `Ukuran file melebihi ${maxSizeMb}MB.`;

      if (inputEl) {
        inputEl.value = '';
      }

      return;
    }

    if (previewUrl) {
      URL.revokeObjectURL(previewUrl);
    }

    previewUrl = URL.createObjectURL(file);
    previewIsImage = file.type.startsWith('image/');
    fileName = file.name;
  }

  function onDrop(event: DragEvent): void {
    event.preventDefault();
    dragOver = false;

    const files = event.dataTransfer?.files;

    if (files?.length && inputEl) {
      inputEl.files = files;
      applyFiles(files);
    }
  }

  function clearFile(event: MouseEvent): void {
    event.stopPropagation();

    if (previewUrl) {
      URL.revokeObjectURL(previewUrl);
    }

    previewUrl = null;
    fileName = null;

    if (inputEl) {
      inputEl.value = '';
    }
  }

  function openLightbox(event: MouseEvent): void {
    if (!displayUrl || !displayIsImage) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    lightboxOpen = true;
  }
</script>

<div class={cn('file-dropzone', dragOver && 'is-dragover', invalid && 'is-invalid', className)}>
  <div
    class="file-dropzone-surface"
    role="button"
    tabindex="0"
    ondragover={(event) => {
      event.preventDefault();
      dragOver = true;
    }}
    ondragleave={() => (dragOver = false)}
    ondrop={onDrop}
    onclick={() => inputEl?.click()}
    onkeydown={(event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        inputEl?.click();
      }
    }}
  >
    {#if displayUrl}
      <div class="file-dropzone-preview">
        <button type="button" class="file-dropzone-preview-trigger" onclick={openLightbox}>
          {#if displayIsImage}
            <img src={displayUrl} alt={fileName ?? 'Pratinjau'} />
          {:else}
            <span class="file-dropzone-document">
              <FileText size={32} />
              <span class="small text-break">{fileName ?? 'Dokumen PDF'}</span>
            </span>
          {/if}
        </button>
        <button
          type="button"
          class="file-dropzone-remove"
          onclick={clearFile}
          aria-label="Hapus file"
        >
          <X size={14} />
        </button>
      </div>
    {:else}
      <div class="file-dropzone-empty">
        <ImagePlus size={28} />
        <p class="mb-0 small text-muted">Seret &amp; lepas gambar, atau klik untuk memilih</p>
      </div>
    {/if}
  </div>

  <input
    bind:this={inputEl}
    type="file"
    {name}
    {accept}
    required={required && !resolvedExistingUrl}
    class="visually-hidden"
    onchange={(event) => applyFiles((event.target as HTMLInputElement).files)}
  />

  {#if error}
    <div class="invalid-feedback d-block">{error}</div>
  {/if}
</div>

{#if lightboxOpen && displayUrl && displayIsImage}
  <ImageLightbox src={displayUrl} alt={fileName ?? 'Pratinjau'} bind:open={lightboxOpen} />
{/if}

<style>
  .file-dropzone-surface {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 10rem;
    padding: 1rem;
    border: 2px dashed var(--neo-border-color);
    border-radius: 0.75rem;
    background-color: var(--neo-tertiary-bg);
    cursor: pointer;
    transition:
      border-color 0.15s ease,
      background-color 0.15s ease;
  }

  .file-dropzone-surface:hover,
  .file-dropzone.is-dragover .file-dropzone-surface {
    border-color: var(--neo-primary);
    background-color: rgba(var(--neo-primary-rgb), 0.05);
  }

  .file-dropzone.is-invalid .file-dropzone-surface {
    border-color: var(--neo-danger);
  }

  .file-dropzone-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: var(--neo-secondary-color, #6c757d);
    text-align: center;
  }

  .file-dropzone-preview {
    position: relative;
    max-width: 100%;
  }

  .file-dropzone-preview-trigger {
    display: block;
    max-width: 100%;
    padding: 0;
    border: none;
    background: none;
  }

  .file-dropzone-preview img {
    display: block;
    max-width: 100%;
    max-height: 12rem;
    border-radius: 0.5rem;
    cursor: zoom-in;
  }

  .file-dropzone-document {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    cursor: default;
  }

  .file-dropzone-remove {
    position: absolute;
    top: -0.5rem;
    right: -0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border: none;
    border-radius: 50%;
    color: #fff;
    background-color: var(--neo-danger);
  }
</style>

<script lang="ts">
  import FileText from '@lucide/svelte/icons/file-text';
  import ImagePlus from '@lucide/svelte/icons/image-plus';
  import X from '@lucide/svelte/icons/x';
  import MediaPickerModal from '@/components/media/media-picker-modal.svelte';
  import { cn, storageUrl } from '@/lib/utils';
  import type { MediaAccept, MediaItem } from '@/types/media';

  /**
   * Form field backed by the media library. Renders a hidden
   * `<input {name}>` holding the stored path so Inertia's `<Form>` submits
   * it like any other field; the file itself is chosen (or uploaded) in
   * the picker modal.
   */
  let {
    name,
    value = null,
    accept = 'image',
    required = false,
    invalid = false,
    class: className = '',
  }: {
    name: string;
    value?: string | null;
    accept?: MediaAccept;
    required?: boolean;
    invalid?: boolean;
    class?: string;
  } = $props();

  let path = $state<string | null>(null);
  let pickerOpen = $state(false);

  $effect(() => {
    path = value;
  });

  const url = $derived(storageUrl(path));
  const isImage = $derived(!/\.pdf($|\?)/i.test(path ?? ''));

  function onSelect(media: MediaItem): void {
    path = media.path;
  }
</script>

<div class={cn('media-field', invalid && 'is-invalid', className)}>
  <input type="hidden" {name} value={path ?? ''} />

  <div class="media-field-surface">
    {#if url}
      <div class="media-field-preview">
        {#if isImage}
          <img src={url} alt="Pratinjau" />
        {:else}
          <span class="d-flex flex-column align-items-center gap-2 py-2">
            <FileText size={32} />
            <span class="small text-break">{path?.split('/').pop()}</span>
          </span>
        {/if}
        {#if !required}
          <button
            type="button"
            class="media-field-remove"
            onclick={() => (path = null)}
            aria-label="Lepas media"
          >
            <X size={14} />
          </button>
        {/if}
      </div>
    {:else}
      <div class="d-flex flex-column align-items-center gap-2 text-muted small">
        <ImagePlus size={28} />
        Belum ada media dipilih
      </div>
    {/if}

    <button
      type="button"
      class="btn btn-sm btn-outline-primary mt-2"
      onclick={() => (pickerOpen = true)}
    >
      {url ? 'Ganti Media' : 'Pilih Media'}
    </button>
  </div>
</div>

<MediaPickerModal bind:open={pickerOpen} {accept} {onSelect} />

<style>
  .media-field-surface {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    border: 2px dashed var(--neo-border-color);
    border-radius: 0.5rem;
    text-align: center;
  }

  .media-field.is-invalid .media-field-surface {
    border-color: var(--neo-danger);
  }

  .media-field-preview {
    position: relative;
    max-width: 100%;
  }

  .media-field-preview img {
    display: block;
    max-width: 100%;
    max-height: 12rem;
    border-radius: 0.5rem;
  }

  .media-field-remove {
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

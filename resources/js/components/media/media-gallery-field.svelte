<script lang="ts">
  import ImagePlus from '@lucide/svelte/icons/image-plus';
  import X from '@lucide/svelte/icons/x';
  import Lightbox from '@/components/ui/lightbox.svelte';
  import MediaPickerModal from '@/components/media/media-picker-modal.svelte';
  import { storageUrl } from '@/lib/utils';
  import type { MediaItem } from '@/types/media';

  type GalleryItem = { image_url: string; description: string | null };

  /**
   * Multi-image field backed by the media library. Submits
   * `{name}[i][image_url]` and `{name}[i][description]` for every image.
   */
  let {
    name = 'galleries',
    value = [],
    errors = {},
  }: {
    name?: string;
    value?: GalleryItem[];
    errors?: Record<string, string>;
  } = $props();

  let items = $state<GalleryItem[]>([]);
  let pickerOpen = $state(false);
  let lightboxOpen = $state(false);
  let lightboxIndex = $state(0);

  const lightboxImages = $derived(
    items.map((item, position) => ({
      url: storageUrl(item.image_url) ?? '',
      title: item.description || `Gambar ${position + 1}`,
    })),
  );

  function preview(index: number): void {
    lightboxIndex = index;
    lightboxOpen = true;
  }

  $effect(() => {
    items = value.map((item) => ({ ...item }));
  });

  function onSelect(media: MediaItem): void {
    if (items.some((item) => item.image_url === media.path)) {
      return;
    }

    items.push({ image_url: media.path, description: null });
  }

  function remove(index: number): void {
    items.splice(index, 1);
  }
</script>

<div class="d-flex flex-column gap-3">
  {#if items.length}
    <div class="gallery-grid">
      {#each items as item, index (item.image_url)}
        <div class="gallery-item">
          <input type="hidden" name={`${name}[${index}][image_url]`} value={item.image_url} />
          <div class="position-relative">
            <button
              type="button"
              class="gallery-preview"
              aria-label="Pratinjau gambar"
              onclick={() => preview(index)}
            >
              <img src={storageUrl(item.image_url) ?? ''} alt="" loading="lazy" />
            </button>
            <button
              type="button"
              class="gallery-remove"
              aria-label="Hapus dari galeri"
              onclick={() => remove(index)}
            >
              <X size={14} />
            </button>
          </div>
          <input
            type="text"
            class="form-control form-control-sm mt-2"
            class:is-invalid={!!errors[`${name}.${index}.description`]}
            name={`${name}[${index}][description]`}
            placeholder="Keterangan (opsional)"
            maxlength="255"
            bind:value={item.description}
          />
          {#if errors[`${name}.${index}.image_url`]}
            <div class="invalid-feedback d-block">{errors[`${name}.${index}.image_url`]}</div>
          {/if}
        </div>
      {/each}
    </div>
  {:else}
    <p class="text-muted small mb-0">Belum ada gambar galeri.</p>
  {/if}

  <div>
    <button
      type="button"
      class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-2"
      onclick={() => (pickerOpen = true)}
    >
      <ImagePlus size={16} />
      Tambah Gambar
    </button>
  </div>
</div>

<Lightbox bind:open={lightboxOpen} bind:index={lightboxIndex} images={lightboxImages} />

<MediaPickerModal bind:open={pickerOpen} accept="image" multiple title="Pilih Gambar Galeri" {onSelect} />

<style>
  .gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
    gap: 0.75rem;
  }

  .gallery-item img {
    display: block;
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    border-radius: 0.5rem;
  }

  .gallery-preview {
    display: block;
    width: 100%;
    padding: 0;
    cursor: zoom-in;
    background: none;
    border: none;
  }

  .gallery-remove {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
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

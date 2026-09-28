<script lang="ts">
  import X from '@lucide/svelte/icons/x';
  import { portal } from '@/lib/dom';

  let {
    src,
    alt = 'Pratinjau',
    open = $bindable(false),
  }: {
    src: string;
    alt?: string;
    open?: boolean;
  } = $props();

  function close(): void {
    open = false;
  }

  function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
      close();
    }
  }
</script>

<svelte:window onkeydown={open ? onKeydown : undefined} />

{#if open}
  <div
    use:portal
    class="image-lightbox-backdrop"
    role="dialog"
    aria-modal="true"
    aria-label={alt}
    tabindex="-1"
    onclick={close}
    onkeydown={onKeydown}
  >
    <button type="button" class="image-lightbox-close" onclick={close} aria-label="Tutup">
      <X size={20} />
    </button>
    <button
      type="button"
      class="image-lightbox-image"
      onclick={(event) => event.stopPropagation()}
    >
      <img {src} {alt} />
    </button>
  </div>
{/if}

<style>
  .image-lightbox-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1080;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background-color: rgba(0, 0, 0, 0.85);
    cursor: zoom-out;
  }

  .image-lightbox-image {
    padding: 0;
    border: none;
    background: none;
    cursor: default;
  }

  .image-lightbox-backdrop img {
    max-width: 100%;
    max-height: 100%;
    border-radius: 0.5rem;
    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.5);
    display: block;
  }

  .image-lightbox-close {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border: none;
    border-radius: 50%;
    color: #fff;
    background-color: rgba(255, 255, 255, 0.15);
  }

  .image-lightbox-close:hover {
    background-color: rgba(255, 255, 255, 0.3);
  }
</style>
